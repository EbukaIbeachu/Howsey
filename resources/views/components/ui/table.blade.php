{{--
    x-ui.table — table scaffolding.
    Keeps Bootstrap's `.table` visuals (already loaded, already covered by the
    dark-theme rules in app-shell.css) and adds the two semantic pieces
    Bootstrap omits: scope="col" on header cells and an accessible <caption>.

    Props
      headers (array|null)  header cells. Each entry is either a plain string or
                            ['label' => string, 'class' => string, 'scope' => string].
                            Omit to write your own <thead> in the slot.
      caption (string|null) accessible description of the table
                            (visually rendered above the table)
      id      (string|null) id placed on the <table>. Client-side table search
                            helpers target these ids (e.g. "tenantsTable"), so
                            pass the existing value through unchanged.
      sticky  (bool)        sticky header when the table scrolls vertically
      striped (bool)        Bootstrap zebra striping
      hover   (bool)        row hover highlight — default true

    Slots
      $slot  the <tbody> (plus <tfoot> if needed)

    Extra attributes (class, data-*) go on the <table> element.
--}}
@props([
    'headers' => null,
    'caption' => null,
    'id' => null,
    'sticky' => false,
    'striped' => false,
    'hover' => true,
])

@php
    $classes = ['table', 'align-middle'];

    if ($striped) {
        $classes[] = 'table-striped';
    }

    if ($hover) {
        $classes[] = 'table-hover';
    }

    if ($sticky) {
        $classes[] = 'ui-table--sticky';
    }
@endphp

<div class="table-responsive">
    <table {{ $attributes->class($classes)->merge(array_filter(['id' => $id], fn ($value) => $value !== null)) }}>
        @if($caption)
            <caption class="ui-table__caption">{{ $caption }}</caption>
        @endif

        @if($headers)
            <thead>
                <tr>
                    @foreach($headers as $header)
                        @php
                            $label = is_array($header) ? ($header['label'] ?? '') : $header;
                            $cellClass = is_array($header) ? ($header['class'] ?? null) : null;
                            $scope = is_array($header) ? ($header['scope'] ?? 'col') : 'col';
                        @endphp
                        <th scope="{{ $scope }}" @if($cellClass) class="{{ $cellClass }}" @endif>{{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
        @endif

        {{ $slot }}
    </table>
</div>
