@extends('layouts.frontend')

@section('title', $magazineIssue->title)

@push('styles')
<style>
    .reader-shell {
        user-select: none;
    }

    .reader-shell img {
        pointer-events: none;
    }

    .issue-nav {
        position: sticky;
        top: 0;
        z-index: 2;
        background: #fff;
        border-bottom: 1px solid #e5e5e5;
    }

    .pdf-reader {
        width: 100%;
        min-height: calc(100vh - 150px);
        border: 1px solid #e5e5e5;
        background: #f8f9fa;
    }

    .pdf-js-reader {
        border: 1px solid #e5e5e5;
        background: #f6f6f6;
    }

    .pdf-js-toolbar {
        position: sticky;
        top: 65px;
        z-index: 1;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: center;
        gap: .5rem;
        padding: .75rem;
        border-bottom: 1px solid #e5e5e5;
        background: #fff;
    }

    .pdf-js-canvas-wrap {
        min-height: calc(100vh - 230px);
        overflow: auto;
        padding: 1rem;
        text-align: center;
    }

    #pdf-canvas {
        max-width: 100%;
        background: #fff;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .14);
    }

    .pdf-js-fallback {
        display: none;
    }

    @media print {
        body * {
            visibility: hidden !important;
        }
    }
</style>
@endpush

@section('content')
<div class="reader-shell" oncontextmenu="return false">
    <div class="issue-nav py-3">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="{{ route('user.dashboard') }}" class="btn btn-outline-dark">Back</a>
            <strong>{{ $magazineIssue->title }}</strong>
            <span class="text-muted small">
                {{ $magazineIssue->issue_date ? \Carbon\Carbon::parse($magazineIssue->issue_date)->format('M Y') : '' }}
            </span>
        </div>
    </div>

    <div class="container my-5">
        <div class="mx-auto" style="max-width: 900px;">
            @php
                $pdfUrl = $magazineIssue->pdf_url;

                if ($pdfUrl && !filter_var($pdfUrl, FILTER_VALIDATE_URL)) {
                    $pdfUrl = asset(ltrim($pdfUrl, '/'));
                }
            @endphp

            @if($magazineIssue->description)
                <p class="lead text-muted">{{ $magazineIssue->description }}</p>
            @endif

            @if($pdfUrl)
                <div class="pdf-js-reader mb-5" data-pdf-url="{{ $pdfUrl }}">
                    <div class="pdf-js-toolbar">
                        <button type="button" class="btn btn-outline-dark btn-sm" id="pdf-prev">Previous</button>
                        <span class="small text-muted">
                            Page <span id="pdf-page-num">1</span> / <span id="pdf-page-count">1</span>
                        </span>
                        <button type="button" class="btn btn-outline-dark btn-sm" id="pdf-next">Next</button>
                        <button type="button" class="btn btn-outline-dark btn-sm" id="pdf-zoom-out">-</button>
                        <span class="small text-muted" id="pdf-zoom-label">100%</span>
                        <button type="button" class="btn btn-outline-dark btn-sm" id="pdf-zoom-in">+</button>
                    </div>
                    <div class="pdf-js-canvas-wrap">
                        <canvas id="pdf-canvas"></canvas>
                        <div class="pdf-js-fallback" id="pdf-fallback">
                            <object class="pdf-reader" data="{{ $pdfUrl }}" type="application/pdf">
                                <iframe class="pdf-reader" src="{{ $pdfUrl }}" title="{{ $magazineIssue->title }}"></iframe>
                            </object>
                        </div>
                    </div>
                </div>
            @endif

            @forelse($magazineIssue->articles as $article)
                <article class="py-5 border-bottom">
                    <div class="d-flex justify-content-between gap-3 mb-3">
                        <h2 class="fw-bold mb-0">{{ $article->title }}</h2>
                        <span class="badge align-self-start {{ ($article->access_type ?? 'free') === 'paid' ? 'text-bg-warning' : 'text-bg-secondary' }}">
                            {{ ucfirst($article->access_type ?? 'free') }}
                        </span>
                    </div>

                    @if($article->summary)
                        <p class="lead text-muted">{{ $article->summary }}</p>
                    @endif

                    @if($article->article_featured_image_url || $article->featured_image_url)
                        <img src="{{ asset($article->article_featured_image_url ?: $article->featured_image_url) }}" class="img-fluid w-100 my-4" alt="">
                    @endif

                    @foreach($article->blocks as $block)
                        @php $data = $block->block_data; @endphp

                        @if(!empty($data['content']))
                            <div class="mb-4" style="line-height: 1.9;">
                                {!! $data['content'] !!}
                            </div>
                        @endif

                        @if(!empty($data['image']))
                            <img src="{{ asset($data['image']) }}" class="img-fluid w-100 my-4" alt="">
                        @endif
                    @endforeach
                </article>
            @empty
                <p class="text-muted">No published articles are attached to this magazine issue yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
@if(!empty($pdfUrl))
<script type="module">
    import * as pdfjsLib from 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.10.38/pdf.min.mjs';

    const reader = document.querySelector('.pdf-js-reader');

    if (reader && pdfjsLib) {
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.10.38/pdf.worker.min.mjs';

        const canvas = document.getElementById('pdf-canvas');
        const context = canvas.getContext('2d');
        const fallback = document.getElementById('pdf-fallback');
        const pageNum = document.getElementById('pdf-page-num');
        const pageCount = document.getElementById('pdf-page-count');
        const zoomLabel = document.getElementById('pdf-zoom-label');
        const prevButton = document.getElementById('pdf-prev');
        const nextButton = document.getElementById('pdf-next');
        const zoomInButton = document.getElementById('pdf-zoom-in');
        const zoomOutButton = document.getElementById('pdf-zoom-out');

        let pdfDoc = null;
        let currentPage = 1;
        let scale = 1.2;
        let isRendering = false;
        let pendingPage = null;

        const showFallback = () => {
            canvas.style.display = 'none';
            fallback.style.display = 'block';
        };

        const updateButtons = () => {
            prevButton.disabled = currentPage <= 1;
            nextButton.disabled = !pdfDoc || currentPage >= pdfDoc.numPages;
            zoomLabel.textContent = `${Math.round(scale * 100)}%`;
        };

        const renderPage = (pageNumber) => {
            isRendering = true;

            pdfDoc.getPage(pageNumber).then((page) => {
                const viewport = page.getViewport({ scale });
                canvas.height = viewport.height;
                canvas.width = viewport.width;

                return page.render({
                    canvasContext: context,
                    viewport,
                }).promise;
            }).then(() => {
                isRendering = false;
                pageNum.textContent = currentPage;
                updateButtons();

                if (pendingPage !== null) {
                    const nextPage = pendingPage;
                    pendingPage = null;
                    renderPage(nextPage);
                }
            }).catch(showFallback);
        };

        const queueRenderPage = (pageNumber) => {
            if (isRendering) {
                pendingPage = pageNumber;
                return;
            }

            renderPage(pageNumber);
        };

        pdfjsLib.getDocument(reader.dataset.pdfUrl).promise.then((pdf) => {
            pdfDoc = pdf;
            pageCount.textContent = pdf.numPages;
            updateButtons();
            renderPage(currentPage);
        }).catch(showFallback);

        prevButton.addEventListener('click', () => {
            if (currentPage <= 1) {
                return;
            }

            currentPage -= 1;
            queueRenderPage(currentPage);
        });

        nextButton.addEventListener('click', () => {
            if (!pdfDoc || currentPage >= pdfDoc.numPages) {
                return;
            }

            currentPage += 1;
            queueRenderPage(currentPage);
        });

        zoomInButton.addEventListener('click', () => {
            scale = Math.min(scale + 0.2, 3);
            queueRenderPage(currentPage);
            updateButtons();
        });

        zoomOutButton.addEventListener('click', () => {
            scale = Math.max(scale - 0.2, 0.6);
            queueRenderPage(currentPage);
            updateButtons();
        });
    } else {
        document.getElementById('pdf-canvas')?.remove();
        const fallback = document.getElementById('pdf-fallback');

        if (fallback) {
            fallback.style.display = 'block';
        }
    }
</script>
@endif
<script>
    document.addEventListener('keydown', function (event) {
        const key = event.key.toLowerCase();
        if ((event.ctrlKey || event.metaKey) && ['s', 'p'].includes(key)) {
            event.preventDefault();
        }
    });
</script>
@endpush
