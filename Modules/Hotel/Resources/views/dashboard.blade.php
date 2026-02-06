@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Hotel Management Dashboard</h1>
                                    
            <div class="row">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title">Available Rooms</h5>
                            <h2 class="mb-0">--</h2>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">Occupied Rooms</h5>
                            <h2 class="mb-0">--</h2>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h5 class="card-title">Today's Check-ins</h5>
                            <h2 class="mb-0">--</h2>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5 class="card-title">Today's Check-outs</h5>
                            <h2 class="mb-0">--</h2>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">Quick Access</h4>
                        </div>
                        <div class="card-body">
                            <p class="text-center text-muted py-5">
                                <i class="fas fa-hotel fa-3x mb-3"></i><br>
                                Hotel Management Module is Active!<br>
                                <small>Phase 1 Complete - UI components coming in Phase 2</small>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
