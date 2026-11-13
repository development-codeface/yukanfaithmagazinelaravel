@php
    $issue = $article->issue;
    $canReadMagazine = auth()->check() && $issue && auth()->user()->canReadMagazine($issue);
    $canRead = $canReadMagazine || (auth()->check() && !$issue && auth()->user()->canReadArticle($article));
    $readUrl = auth()->check() ? route('user.article', $article) : route('frontend.article', $article);
@endphp
<div class="magazine-actions">
    <a class="magazine-action magazine-action--read" href="{{ $readUrl }}">
        <i class="fa-solid fa-book-open" aria-hidden="true"></i>
        <span>{{ auth()->check() ? 'Read Article' : 'Read More' }}</span>
    </a>
    @if(auth()->check() && $issue)
        <a class="magazine-action magazine-action--read" href="{{ route('user.magazine.reader', $issue) }}">
            <i class="fa-solid fa-book-open" aria-hidden="true"></i>
            <span>Read Magazine</span>
        </a>
    @endif
    @if(!$canRead)
            <a class="magazine-action magazine-action--subscribe" href="{{ route('user.subscriptions') }}">Subscribe <span aria-hidden="true">&rarr;</span></a>
            @if(auth()->check() && $issue && count($issue->prices()))
                <a class="magazine-action magazine-action--buy" href="{{ route('user.subscriptions', ['magazine' => $issue->id]) }}">Buy this magazine</a>
            @endif
    @endif
</div>
