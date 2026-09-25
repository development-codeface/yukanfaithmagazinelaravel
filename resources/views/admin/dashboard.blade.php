@extends('layouts.admin')

@section('content')
<div class="content-body">
    <div class="row">
        <div class="col-xl-12">

        <div class="magazine-dashboard">

            {{-- ================= HEADER / OVERVIEW ================= --}}
            <div class="card mb-4"
                 style="background-image: url('{{ asset('css/img/magazine-bg.jpg') }}');
                        background-size: cover;">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fa-solid fa-newspaper mr-2"></i>
                        Magazine Admin Dashboard
                    </h4>
                </div>

                <div class="card-body">
                    <div class="row text-center">

                        <div class="col-md-3">
                            <div class="p-3 bg-light rounded shadow-sm">
                                <h6>Total Articles</h6>
                                <h2 class="text-primary">128</h2>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="p-3 bg-light rounded shadow-sm">
                                <h6>Published</h6>
                                <h2 class="text-success">92</h2>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="p-3 bg-light rounded shadow-sm">
                                <h6>Drafts</h6>
                                <h2 class="text-warning">21</h2>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="p-3 bg-light rounded shadow-sm">
                                <h6>Authors</h6>
                                <h2 class="text-primary">14</h2>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- ================= ARTICLE ACTIVITY CHART ================= --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fa-solid fa-chart-line mr-2"></i>
                        Publishing Activity
                    </h4>
                </div>
                <div class="card-body">
                    <canvas id="articleChart" height="100"></canvas>
                </div>
            </div>

            {{-- ================= CATEGORY SUMMARY ================= --}}
            <div class="card mt-4">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fa-solid fa-list mr-2"></i>
                        Category Overview
                    </h4>
                </div>

                <div class="card-body table-responsive">
                    <table class="table table-bordered text-center">
                        <thead class="thead-light">
                            <tr>
                                <th class="text-left">Category</th>
                                <th>Articles</th>
                                <th>Published</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-left">Editorial</td>
                                <td>32</td>
                                <td>25</td>
                                <td><span class="badge bg-success">Active</span></td>
                            </tr>
                            <tr>
                                <td class="text-left">Industry News</td>
                                <td>28</td>
                                <td>20</td>
                                <td><span class="badge bg-success">Active</span></td>
                            </tr>
                            <tr>
                                <td class="text-left">Interviews</td>
                                <td>18</td>
                                <td>12</td>
                                <td><span class="badge bg-warning">Needs Review</span></td>
                            </tr>
                            <tr>
                                <td class="text-left">Events</td>
                                <td>22</td>
                                <td>15</td>
                                <td><span class="badge bg-secondary">Upcoming</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ================= RECENT ARTICLES ================= --}}
            <div class="card mt-4">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fa-solid fa-pen-nib mr-2"></i>
                        Recent Articles
                    </h4>
                </div>

                <div class="card-body table-responsive">
                    <table class="table table-bordered text-left">
                        <thead class="thead-light">
                            <tr>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Published On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>The Future of Dairy Tech</td>
                                <td>Anita R</td>
                                <td>Industry News</td>
                                <td><span class="badge bg-success">Published</span></td>
                                <td>18 Jan 2026</td>
                            </tr>
                            <tr>
                                <td>Milma YPO Leadership Talk</td>
                                <td>Suresh K</td>
                                <td>Editorial</td>
                                <td><span class="badge bg-warning">Draft</span></td>
                                <td>—</td>
                            </tr>
                            <tr>
                                <td>Upcoming Cooperative Events</td>
                                <td>Meera V</td>
                                <td>Events</td>
                                <td><span class="badge bg-secondary">Scheduled</span></td>
                                <td>05 Feb 2026</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        </div>
    </div>
</div>

{{-- Font Awesome --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>

{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    new Chart(document.getElementById('articleChart'), {
        type: 'line',
        data: {
            labels: ['Aug','Sep','Oct','Nov','Dec','Jan'],
            datasets: [{
                label: 'Articles Published',
                data: [8, 12, 15, 18, 20, 19],
                borderColor: '#2563eb',
                tension: 0.4,
                fill: false
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: true }
            }
        }
    });

});
</script>
@endsection
