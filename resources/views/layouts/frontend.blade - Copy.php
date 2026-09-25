<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Yukan Faith Magazine')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Swiper -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-element-bundle.min.js"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        swiper-container {
            width: 100%;
            height: 85vh;
        }

        @media (max-width: 768px) {
            swiper-container {
                height: 60vh !important;
            }
        }
    </style>

    @stack('styles')
</head>

<body class="bg-white">

<!-- ================= HEADER ================= -->
<nav class="w-full flex flex-col items-center border-b border-gray-200">

    <!-- Top Header -->
    <div class="flex mt-10 items-center justify-between w-full max-w-[1200px] px-6">

        <!-- Subscribe -->
        <a href="#"
           class="bg-[#1e3a5f] text-white font-medium px-5 py-2 hover:bg-[#162c48] transition duration-300">
            Subscribe
        </a>

        <!-- Logo -->
        <a href="{{ route('home') }}">
            <img src="{{ asset('uploads/yukan.png') }}"
                 >
        </a>

        <!-- Purchase -->
        <a href="#"
           class="bg-[#1e3a5f] text-white font-medium px-5 py-2 hover:bg-[#162c48] transition duration-300">
            Purchase
        </a>

    </div>

    <!-- Category Menu -->
    <div class="flex flex-wrap justify-center gap-8 mt-6 mb-6 text-gray-700 text-sm font-medium">

        @foreach(\App\Models\Category::all() as $cat)
            <a href="{{ route('frontend.category', $cat->id) }}"
               class="hover:text-red-900 transition duration-300">
                {{ $cat->category_name }}
            </a>
        @endforeach

    </div>

</nav>


<!-- ================= PAGE CONTENT ================= -->
@yield('content')


<!-- ================= FOOTER ================= -->
<footer class="mt-20 bg-gray-100 py-8 text-center text-sm text-gray-600">
    © {{ date('Y') }} Yukan Faith Magazine. All rights reserved.
</footer>

@stack('scripts')

</body>
</html>
