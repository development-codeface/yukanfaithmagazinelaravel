@extends('layouts.frontend')

@section('title', $article->title)

@section('content')

<section class="wrapper grid lg:grid-cols-[.45fr,.55fr] gap-16 pt-16 pb-20 px-6">

    {{-- LEFT SIDE IMAGE (STICKY) --}}
    <div class="w-full lg:sticky top-10 h-max">

        <img src="{{ asset($article->featured_image_url) }}"
             class="w-full object-contain">

        <div class="mt-2 text-sm text-gray-500 flex items-center gap-2">
            <i class="fas fa-camera"></i>
            <span>{{ $article->left_image_title }}</span>
        </div>

    </div>


    {{-- RIGHT SIDE CONTENT --}}
   <div class="sec-scroll flex flex-col gap-8 post-single pr-4 lg:pr-10">


       <h1 class="text-3xl lg:text-4xl font-bold leading-tight mb-6">

              {{ $article->summary }}
        </h1>

        {{-- BLOCK CONTENT --}}
        @foreach($article->blocks as $block)

            @php $data = $block->block_data; @endphp

            @if(!empty($data['content']))
               <div class="leading-8 text-gray-800 space-y-6">
                    {!! $data['content'] !!}
                </div>
            @endif


            @if(!empty($data['image']))
                <img src="{{ asset($data['image']) }}"
                     class="w-full object-contain">
            @endif

           
        @endforeach


        {{-- BUY BUTTON --}}
        @if($article->buy_button_link)
            <a href="{{ $article->buy_button_link }}"
               target="_blank"
               class="mt-6 bg-black text-white px-8 py-3 w-max hover:bg-gray-800 transition">
                Buy Now
            </a>
        @endif

    </div>

</section>

@endsection
