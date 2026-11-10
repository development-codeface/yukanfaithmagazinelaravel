@php
    $issue = $article->issue;
    $canRead = auth()->check() && ($issue ? auth()->user()->canReadMagazine($issue) : auth()->user()->canReadArticle($article));
@endphp
<div class="d-flex flex-column align-items-start gap-2 mt-3">
    <a class="btn btn-light" href="{{ auth()->check() ? route('user.article', $article) : route('frontend.article', $article) }}">{{ auth()->check() ? 'Read Article' : 'Read More' }}</a>
    @if(!$canRead)
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-warning" href="{{ route('user.subscriptions') }}">Subscribe</a>
            @if(auth()->check() && $issue && count($issue->prices()))
                <a class="btn btn-outline-secondary bg-white" href="{{ route('user.subscriptions', ['magazine' => $issue->id]) }}">Buy this magazine</a>
            @endif
        </div>
    @endif
</div>
