@extends('layouts.main')

@section('title', 'Live View Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Live View Dashboard</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h4>Live Cameras</h4>
                            <p>Monitor all branch CCTV cameras in real-time</p>
                            <a href="{{ route('live.cameras.index') }}" class="btn btn-primary">
                                <i class="fas fa-camera"></i> View Cameras
                            </a>
                        </div>
                        <div class="col-md-6">
                            <h4>Live Charts</h4>
                            <p>Real-time transaction and business analytics</p>
                            <a href="{{ route('live.charts.index') }}" class="btn btn-success">
                                <i class="fas fa-chart-line"></i> View Charts
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection