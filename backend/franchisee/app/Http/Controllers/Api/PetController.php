<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\PetServiceInterface;
use App\Http\Requests\Pet\StorePetRequest;
use App\Http\Requests\Pet\UpdatePetRequest;
use App\Http\Resources\PetResource;
use App\Models\CustomerItem;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;

class PetController extends Controller
{
    public function __construct(
        private PetServiceInterface $petService
    ) {}

    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return PetResource::collection($this->petService->listPets());
    }

    public function store(StorePetRequest $request): PetResource
    {
        $pet = $this->petService->createPet(
            $request->validated(),
            $request->file('image')
        );

        return new PetResource($pet);
    }

    public function show(CustomerItem $pet): PetResource
    {
        return new PetResource($pet);
    }

    public function update(UpdatePetRequest $request, CustomerItem $pet): PetResource
    {
        $pet = $this->petService->updatePet(
            $pet,
            $request->validated(),
            $request->file('image'),
            $request->boolean('remove_image')
        );

        return new PetResource($pet);
    }

    public function destroy(CustomerItem $pet): JsonResponse
    {
        $this->petService->deletePet($pet);
        return response()->json(null, 204);
    }

    public function getByCustomer(Customer $customer): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return PetResource::collection($this->petService->getPetsByCustomer($customer->id));
    }

    public function getHistory(CustomerItem $pet): JsonResponse
    {
        return response()->json($this->petService->getPetHistory($pet));
    }
}
