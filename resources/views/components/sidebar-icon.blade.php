
{{-- Render the outline icon selected by the caller. --}}
@props(['name' => 'home'])

<svg viewBox="0 0 24 24"
     aria-hidden="true"
     fill="none"
     stroke="currentColor"
     stroke-width="1.8"
     stroke-linecap="round"
     stroke-linejoin="round">

    @if($name === 'home')
        <path d="m3 10 9-7 9 7v10H4V10"/>
        <path d="M9 20v-7h6v7"/>

    @elseif($name === 'users')
        <circle cx="9" cy="8" r="4"/>
        <path d="M2 21v-2a7 7 0 0 1 14 0v2"/>
        <path d="M17 4a4 4 0 0 1 0 8"/>
        <path d="M18 15a6 6 0 0 1 4 6"/>

    @elseif($name === 'truck')
        <path d="M2 5h13v12H2z"/>
        <path d="M15 9h4l3 4v4h-7"/>
        <circle cx="6" cy="18" r="2"/>
        <circle cx="18" cy="18" r="2"/>

    @elseif($name === 'grid')
        <rect x="3" y="3" width="7" height="7" rx="1"/>
        <rect x="14" y="3" width="7" height="7" rx="1"/>
        <rect x="3" y="14" width="7" height="7" rx="1"/>
        <rect x="14" y="14" width="7" height="7" rx="1"/>

    @elseif($name === 'box')
        <path d="m12 2 9 5v10l-9 5-9-5V7z"/>
        <path d="M3 7l9 5 9-5M12 12v10"/>

    @elseif($name === 'cart')
        <path d="M2 3h3l3 13h12l2-9H6"/>
        <circle cx="9" cy="20" r="1"/>
        <circle cx="19" cy="20" r="1"/>

    @elseif($name === 'file')
        <path d="M5 2h10l5 5v15H5z"/>
        <path d="M15 2v5h5M9 12h7M9 16h7"/>

    @elseif($name === 'card')
        <rect x="2" y="4" width="20" height="16" rx="2"/>
        <path d="M2 9h20M6 15h6"/>

    @elseif($name === 'chart')
        <rect x="3" y="3" width="18" height="18" rx="2"/>
        <path d="m7 16 4-5 3 2 3-5"/>

    @elseif($name === 'team')
        <circle cx="9" cy="7" r="3"/>
        <path d="M3 21v-2a6 6 0 0 1 12 0v2"/>
        <path d="M17 4a3 3 0 0 1 0 6"/>
        <path d="M17 15a5 5 0 0 1 4 5"/>

    @endif

</svg>
