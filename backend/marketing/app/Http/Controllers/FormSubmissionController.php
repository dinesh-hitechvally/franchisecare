<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Http\Request;

/** The inbox for one form: list, delete and export what people submitted. */
class FormSubmissionController extends Controller
{
    public function index(Request $request, Form $form)
    {
        return FormSubmission::where('form_id', $form->id)->with('contact:id,first_name,last_name,email')
            // search looks through the answers and the linked contact
            ->when($request->query('search'), fn ($q, $v) => $q->where(fn ($w) => $w->where('data', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $v).'%')
                ->orWhereHas('contact', fn ($c) => $c->where('email', 'like', "%$v%")->orWhere('first_name', 'like', "%$v%")->orWhere('last_name', 'like', "%$v%"))))
            ->when($request->query('contact') === 'yes', fn ($q) => $q->whereNotNull('contact_id'))
            ->when($request->query('contact') === 'no', fn ($q) => $q->whereNull('contact_id'))
            ->when($request->query('from'), fn ($q, $v) => $q->where('created_at', '>=', $v.' 00:00:00'))
            ->when($request->query('to'), fn ($q, $v) => $q->where('created_at', '<=', $v.' 23:59:59'))
            ->latest('id')->paginate(25);
    }

    public function destroy(Form $form, FormSubmission $submission)
    {
        abort_unless($submission->form_id === $form->id, 404);
        $submission->delete();

        return response()->noContent();
    }

    public function export(Form $form)
    {
        return response()->streamDownload(function () use ($form) {
            $labels = [];
            FormSubmission::where('form_id', $form->id)->orderBy('id')->each(function ($s) use (&$labels) {
                foreach ($s->data as $a) {
                    $labels[$a['label']] ??= count($labels);
                }
            });

            $out = fopen('php://output', 'w');
            fputcsv($out, array_merge(['Submitted at', 'Contact email'], array_map([self::class, 'safe'], array_keys($labels))));
            FormSubmission::where('form_id', $form->id)->with('contact:id,email')->orderBy('id')->chunkById(500, function ($rows) use ($out, $labels) {
                foreach ($rows as $s) {
                    $line = array_fill(0, count($labels), '');
                    foreach ($s->data as $a) {
                        $line[$labels[$a['label']]] = self::safe((string) ($a['value'] ?? ''));
                    }
                    fputcsv($out, array_merge([$s->created_at, self::safe((string) $s->contact?->email)], $line));
                }
            });
            fclose($out);
        }, 'form-'.$form->id.'-submissions.csv', ['Content-Type' => 'text/csv']);
    }

    /** Neutralise spreadsheet formula injection: anything a visitor typed that starts with = + - @ is shown as text. */
    protected static function safe(string $v): string
    {
        return $v !== '' && in_array($v[0], ['=', '+', '-', '@'], true) ? "'".$v : $v;
    }
}
