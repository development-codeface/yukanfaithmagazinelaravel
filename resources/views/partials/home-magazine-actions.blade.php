@php
    $issue = $article->issue;
    $hasActivePackage = auth()->check() && auth()->user()->hasActiveDigitalSubscription();
    $readUrl = auth()->check() ? route('user.article', $article) : route('frontend.article', $article);
@endphp
<div class="magazine-actions">
    <a class="magazine-action magazine-action--read" href="{{ $readUrl }}">
        <i class="fa-solid fa-book-open" aria-hidden="true"></i>
        <span>Read Article</span>
    </a>
    @if($hasActivePackage && $issue)
        <a class="magazine-action magazine-action--read" href="{{ route('user.magazine.reader', $issue) }}">
            <i class="fa-solid fa-book-open" aria-hidden="true"></i>
            <span>Read Magazine</span>
        </a>
    @endif
    @if($hasActivePackage && !$issue)
        <span class="magazine-action magazine-action--read" aria-disabled="true" title="No magazine is tagged to this article yet.">Read Magazine</span>
    @endif
    @if(!$hasActivePackage)
            <a class="magazine-action magazine-action--subscribe" href="{{ route('user.subscriptions') }}">Subscribe <span aria-hidden="true">&rarr;</span></a>
    @endif
</div>
