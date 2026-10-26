{{--
    x-ui.table.empty-state — colspan-aware "no rows" placeholder.
    Rendered inside a <tbody>, so it replaces the hand-written
    `<tr><td colspan="…">No records</td></tr>` blocks.

    Props
      colspan (int)          number of columns to span (required)
      icon    (string|null)  bootstrap-icons class
      title   (string)       short headline
      text    (string|null)  one sentence explaining the next step

    Extra attributes go on the <tr>.
--}}
@props([
    'colspan',
    'icon' => 'bi bi-inbox',
    'title' => 'No records found',
    'text' => null,
])

<tr {{ $attributes }}>
    <td colspan="{{ $colspan }}">
        <x-ui.empty-state :icon="$icon" :title="$title" :text="$text" inline />
    </td>
</tr>
