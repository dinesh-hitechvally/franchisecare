// Editing text in place in a designer's View tab (email templates, landing pages).
//
// The renderer tags every piece of text that can be edited with data attributes: data-edit (which field), data-idx (which item of a list)
// and data-rich (formatted text). Clicking a tagged element makes it editable; the new value is saved when the person clicks away.
// Enter ends a single-line field and Esc cancels. While editing, the stored value is shown (not the preview), so a merge tag such as
// {{first_name}} is never replaced by its sample text and saved over.
import { ref } from 'vue'
import { sanitize } from '../builder/blocks'

/** @param locked a ref that is true while nothing may be changed (read-only copies, history) */
export function useInlineEdit(locked) {
  const editing = ref(null) // { el, item, key, rich, blockId } while a piece of text is being edited
  const renderTick = ref(0) // bumped after an edit, as the :key of the block's markup, so the block is drawn again as a preview

  /** The click handler of a block's markup. */
  function startEdit(event, block) {
    const el = event.target.closest?.('[data-edit]')
    if (!el || locked.value || el.isContentEditable) return
    if (editing.value) finish() // clicking another piece of text saves the one being edited

    const idx = el.dataset.idx
    const item = idx !== undefined ? (block.items ?? block.links)?.[Number(idx)] : block
    if (!item) return
    const key = el.dataset.edit
    const rich = el.dataset.rich === '1'

    if (rich) el.innerHTML = sanitize(item[key])
    else el.textContent = item[key] ?? ''
    el.contentEditable = rich ? 'true' : 'plaintext-only'
    if (rich) document.execCommand('defaultParagraphSeparator', false, 'p')

    editing.value = { el, item, key, rich, blockId: block.id }
    el.addEventListener('blur', onBlur, { once: true })
    el.addEventListener('keydown', onKey)
    el.focus()
    caretToEnd(el)
  }

  function caretToEnd(el) {
    const range = document.createRange()
    range.selectNodeContents(el)
    range.collapse(false)
    const selection = window.getSelection()
    selection.removeAllRanges()
    selection.addRange(range)
  }

  function onKey(e) {
    if (e.key === 'Escape') {
      e.preventDefault()
      finish(true)
    } else if (e.key === 'Enter' && !editing.value?.rich) {
      e.preventDefault()
      finish()
    } else if (e.key === ' ' && e.target.closest('summary')) {
      e.preventDefault() // a space inside a question would otherwise open or close it
      document.execCommand('insertText', false, ' ')
    }
    e.stopPropagation()
  }

  const onBlur = () => finish()

  function finish(cancel = false) {
    const current = editing.value
    if (!current) return
    editing.value = null
    current.el.removeEventListener('blur', onBlur)
    current.el.removeEventListener('keydown', onKey)

    if (!cancel) {
      const value = current.rich ? sanitize(current.el.innerHTML) : current.el.textContent.replace(/\s*\n\s*/g, ' ').trim()
      if (value !== (current.item[current.key] ?? '')) current.item[current.key] = value
    }
    current.el.removeAttribute('contenteditable')
    renderTick.value++
  }

  return { editing, renderTick, startEdit }
}
