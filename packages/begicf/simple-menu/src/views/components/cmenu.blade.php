@foreach($items as $item)
    @if($item['children']->isNotEmpty())
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink{{ $item->id }}"
               data-bs-toggle="dropdown" aria-expanded="false">
                {{ $item->title }}
            </a>
            <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink{{ $item->id }}">
                @foreach($item['children'] as $child)
                    <a class="dropdown-item {{ (request()->is($child->url)) ? 'active' : '' }}" href="{{ url($child->url) }}">{{ $child->title }}</a>
                @endforeach
            </div>
        </li>
    @else
        <li class="nav-item">
            <a class="nav-link {{ (request()->is($item->url)) ? 'active' : '' }}" href="{{ url($item->url) }}">{{ $item->title }}</a>
        </li>
    @endif
@endforeach

