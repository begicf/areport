@foreach($items as $item)
    @continue(strcasecmp($item->title, 'Taxonomy') === 0)

    @if($item['children']->isNotEmpty())
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink{{ $item->id }}"
               role="button" data-bs-toggle="dropdown" aria-expanded="false">
                {{ $item->title }}
            </a>
            <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink{{ $item->id }}">
                @foreach($item['children']->sortBy('order') as $child)
                    <a class="dropdown-item {{ request()->is(ltrim($child->url, '/')) ? 'active' : '' }}"
                       href="{{ url($child->url) }}">
                        {{ $child->title }}
                    </a>
                @endforeach
            </div>
        </li>
    @else
        <li class="nav-item">
            <a class="nav-link {{ request()->is(ltrim($item->url, '/')) ? 'active' : '' }}" href="{{ url($item->url) }}">
                {{ $item->title }}
            </a>
        </li>
    @endif
@endforeach

<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle {{ request()->is('taxonomy/*') || request()->is('taxonomy') ? 'active' : '' }}"
       href="#"
       id="navbarDropdownTaxonomy"
       role="button"
       data-bs-toggle="dropdown"
       aria-expanded="false">
        Taxonomy
    </a>
    <div class="dropdown-menu" aria-labelledby="navbarDropdownTaxonomy">
        <a class="dropdown-item {{ request()->is('taxonomy/managing') || request()->is('taxonomy') ? 'active' : '' }}"
           href="{{ url('/taxonomy/managing') }}">
            Active taxonomy
        </a>
        <a class="dropdown-item {{ request()->is('taxonomy/upload') ? 'active' : '' }}"
           href="{{ url('/taxonomy/upload') }}">
            Upload taxonomy
        </a>
    </div>
</li>
