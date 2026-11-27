@props([
    'merchant',
])

@if ($merchant->is_gst_member)
    <span {{ $attributes->merge([
        'class' => 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium border bg-sky-50 text-sky-800 border-sky-200',
    ]) }}>
        GST Member
    </span>
@endif
