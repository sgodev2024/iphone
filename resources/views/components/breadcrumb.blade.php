<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="/" class="mb-0 d-inline-block lh-1 fw-medium text-uppercase">
                trang chủ
            </a>
        </li>
        @foreach ($items as $item)
            <li class="breadcrumb-item active text-uppercase" aria-current="page">
                @isset($item['url'])
                    <a href="{{ $item['url'] }}" class="mb-0 d-inline-block lh-1 fw-medium text-uppercase">
                        {{ $item['label'] }}
                    </a>
                @else
                    <span class="fw-medium">{{ $item['label'] }}</span>
                @endisset
            </li>
        @endforeach
    </ol>
</nav>
