@extends('layouts/layoutMaster')

@section('title', 'Dashboard')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/apex-charts/apex-charts.css')}}" />
@endsection


@section('vendor-script')
<script src="{{asset('assets/vendor/libs/apex-charts/apexcharts.js')}}"></script>
@endsection

@section('page-script')
<script src="{{asset('assets/js/dashboards-crm.js')}}"></script>
@endsection

@section('content')
<div class="row">

  <!-- Sales last year -->
  <div class="mb-4 col-xl-2 col-md-4 col-6">
    <div class="card">
      <div class="pb-0 card-header">
        <h5 class="mb-0 card-title">Sales</h5>
        <small class="text-muted">Last Year</small>
      </div>
      <div id="salesLastYear"></div>
      <div class="pt-0 card-body">
        <div class="gap-3 mt-3 d-flex justify-content-between align-items-center">
          <h4 class="mb-0">175k</h4>
          <small class="text-danger">-16.2%</small>
        </div>
      </div>
    </div>
  </div>

  <!-- Sessions Last month -->
  <div class="mb-4 col-xl-2 col-md-4 col-6">
    <div class="card">
      <div class="pb-0 card-header">
        <h5 class="mb-0 card-title">Sessions</h5>
        <small class="text-muted">Last Month</small>
      </div>
      <div class="card-body">
        <div id="sessionsLastMonth"></div>
        <div class="gap-3 mt-3 d-flex justify-content-between align-items-center">
          <h4 class="mb-0">45.1k</h4>
          <small class="text-success">+12.6%</small>
        </div>
      </div>
    </div>
  </div>

  <!-- Total Profit -->
  <div class="mb-4 col-xl-2 col-md-4 col-6">
    <div class="card">
      <div class="card-body">
        <div class="p-2 mb-2 rounded badge bg-label-danger"><i class="ti ti-currency-dollar ti-md"></i></div>
        <h5 class="pt-2 mb-1 card-title">Total Profit</h5>
        <small class="text-muted">Last week</small>
        <p class="mt-1 mb-2">1.28k</p>
        <div class="pt-1">
          <span class="badge bg-label-secondary">-12.2%</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Total Sales -->
  <div class="mb-4 col-xl-2 col-md-4 col-6">
    <div class="card">
      <div class="card-body">
        <div class="p-2 mb-2 rounded badge bg-label-info"><i class="ti ti-chart-bar ti-md"></i></div>
        <h5 class="pt-2 mb-1 card-title">Total Sales</h5>
        <small class="text-muted">Last week</small>
        <p class="mt-1 mb-2">$4,673</p>
        <div class="pt-1">
          <span class="badge bg-label-secondary">+25.2%</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Revenue Growth -->
  <div class="mb-4 col-xl-4 col-md-8">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between">
          <div class="d-flex flex-column">
            <div class="mb-auto card-title">
              <h5 class="mb-1 text-nowrap">Revenue Growth</h5>
              <small>Weekly Report</small>
            </div>
            <div class="chart-statistics">
              <h3 class="mb-1 card-title">$4,673</h3>
              <span class="badge bg-label-success">+15.2%</span>
            </div>
          </div>
          <div id="revenueGrowth"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Earning Reports Tabs-->
  <div class="mb-4 col-12 col-xl-8">
    <div class="card">
      <div class="card-header d-flex justify-content-between">
        <div class="mb-0 card-title">
          <h5 class="mb-0">Earning Reports</h5>
          <small class="text-muted">Yearly Earnings Overview</small>
        </div>
        <div class="dropdown">
          <button class="p-0 btn" type="button" id="earningReportsTabsId" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="ti ti-dots-vertical ti-sm text-muted"></i>
          </button>
          <div class="dropdown-menu dropdown-menu-end" aria-labelledby="earningReportsTabsId">
            <a class="dropdown-item" href="javascript:void(0);">View More</a>
            <a class="dropdown-item" href="javascript:void(0);">Delete</a>
          </div>
        </div>
      </div>
      <div class="card-body">
        <ul class="gap-4 pb-3 mx-1 nav nav-tabs widget-nav-tabs d-flex flex-nowrap" role="tablist">
          <li class="nav-item">
            <a href="javascript:void(0);" class="nav-link btn active d-flex flex-column align-items-center justify-content-center" role="tab" data-bs-toggle="tab" data-bs-target="#navs-orders-id" aria-controls="navs-orders-id" aria-selected="true">
              <div class="p-2 rounded badge bg-label-secondary"><i class="ti ti-shopping-cart ti-sm"></i></div>
              <h6 class="mt-2 mb-0 tab-widget-title">Orders</h6>
            </a>
          </li>
          <li class="nav-item">
            <a href="javascript:void(0);" class="nav-link btn d-flex flex-column align-items-center justify-content-center" role="tab" data-bs-toggle="tab" data-bs-target="#navs-sales-id" aria-controls="navs-sales-id" aria-selected="false">
              <div class="p-2 rounded badge bg-label-secondary"><i class="ti ti-chart-bar ti-sm"></i></div>
              <h6 class="mt-2 mb-0 tab-widget-title"> Sales</h6>
            </a>
          </li>
          <li class="nav-item">
            <a href="javascript:void(0);" class="nav-link btn d-flex flex-column align-items-center justify-content-center" role="tab" data-bs-toggle="tab" data-bs-target="#navs-profit-id" aria-controls="navs-profit-id" aria-selected="false">
              <div class="p-2 rounded badge bg-label-secondary"><i class="ti ti-currency-dollar ti-sm"></i></div>
              <h6 class="mt-2 mb-0 tab-widget-title">Profit</h6>
            </a>
          </li>
          <li class="nav-item">
            <a href="javascript:void(0);" class="nav-link btn d-flex flex-column align-items-center justify-content-center" role="tab" data-bs-toggle="tab" data-bs-target="#navs-income-id" aria-controls="navs-income-id" aria-selected="false">
              <div class="p-2 rounded badge bg-label-secondary"><i class="ti ti-chart-pie-2 ti-sm"></i></div>
              <h6 class="mt-2 mb-0 tab-widget-title">Income</h6>
            </a>
          </li>
          <li class="nav-item">
            <a href="javascript:void(0);" class="nav-link btn d-flex align-items-center justify-content-center disabled" role="tab" data-bs-toggle="tab" aria-selected="false">
              <div class="p-2 rounded badge bg-label-secondary"><i class="ti ti-plus ti-sm"></i></div>
            </a>
          </li>
        </ul>
        <div class="p-0 tab-content ms-0 ms-sm-2">
          <div class="tab-pane fade show active" id="navs-orders-id" role="tabpanel">
            <div id="earningReportsTabsOrders"></div>
          </div>
          <div class="tab-pane fade" id="navs-sales-id" role="tabpanel">
            <div id="earningReportsTabsSales"></div>
          </div>
          <div class="tab-pane fade" id="navs-profit-id" role="tabpanel">
            <div id="earningReportsTabsProfit"></div>
          </div>
          <div class="tab-pane fade" id="navs-income-id" role="tabpanel">
            <div id="earningReportsTabsIncome"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Browser States -->
  <div class="mb-4 col-xl-4 col-md-6">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between">
        <div class="m-0 card-title me-2">
          <h5 class="m-0 me-2">Browser States</h5>
          <small class="text-muted">Counter April 2022</small>
        </div>
        <div class="dropdown">
          <button class="p-0 btn" type="button" id="employeeList" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="ti ti-dots-vertical ti-sm text-muted"></i>
          </button>
          <div class="dropdown-menu dropdown-menu-end" aria-labelledby="employeeList">
            <a class="dropdown-item" href="javascript:void(0);">Download</a>
            <a class="dropdown-item" href="javascript:void(0);">Refresh</a>
            <a class="dropdown-item" href="javascript:void(0);">Share</a>
          </div>
        </div>
      </div>
      <div class="card-body">
        <ul class="p-0 m-0">
          <li class="pb-1 mb-4 d-flex align-items-center">
            <img src="{{asset('assets/img/icons/brands/chrome.png')}}" alt="Chrome" height="28" class="rounded me-3">
            <div class="gap-2 d-flex w-100 align-items-center">
              <div class="flex-wrap d-flex justify-content-between flex-grow-1">
                <div>
                  <h6 class="mb-0">Google Chrome</h6>
                </div>

                <div class="gap-2 user-progress d-flex align-items-center">
                  <h6 class="mb-0">90.4%</h6>
                </div>
              </div>
              <div class="chart-progress" data-color="secondary" data-series="85"></div>
            </div>
          </li>
          <li class="pb-1 mb-4 d-flex align-items-center">
            <img src="{{asset('assets/img/icons/brands/safari.png')}}" alt="Safari" height="28" class="rounded me-3">
            <div class="gap-2 d-flex w-100 align-items-center">
              <div class="flex-wrap d-flex justify-content-between flex-grow-1">
                <div>
                  <h6 class="mb-0">Apple Safari</h6>
                </div>
                <div class="gap-2 user-progress d-flex align-items-center">
                  <h6 class="mb-0">70.6%</h6>
                </div>
              </div>
              <div class="chart-progress" data-color="success" data-series="70"></div>
            </div>
          </li>
          <li class="pb-1 mb-4 d-flex align-items-center">
            <img src="{{asset('assets/img/icons/brands/firefox.png')}}" alt="Firefox" height="28" class="rounded me-3">
            <div class="gap-2 d-flex w-100 align-items-center">
              <div class="flex-wrap d-flex justify-content-between flex-grow-1">
                <div>
                  <h6 class="mb-0">Mozilla Firefox</h6>
                </div>
                <div class="gap-2 user-progress d-flex align-items-center">
                  <h6 class="mb-0">35.5%</h6>
                </div>
              </div>
              <div class="chart-progress" data-color="primary" data-series="25"></div>
            </div>
          </li>
          <li class="pb-1 mb-4 d-flex align-items-center">
            <img src="{{asset('assets/img/icons/brands/opera.png')}}" alt="Opera" height="28" class="rounded me-3">
            <div class="gap-2 d-flex w-100 align-items-center">
              <div class="flex-wrap d-flex justify-content-between flex-grow-1">
                <div>
                  <h6 class="mb-0">Opera Mini</h6>
                </div>

                <div class="gap-2 user-progress d-flex align-items-center">
                  <h6 class="mb-0">80.0%</h6>
                </div>
              </div>
              <div class="chart-progress" data-color="danger" data-series="75"></div>
            </div>
          </li>
          <li class="pb-1 mb-4 d-flex align-items-center">
            <img src="{{asset('assets/img/icons/brands/edge.png')}}" alt="Edge" height="28" class="rounded me-3">
            <div class="gap-2 d-flex w-100 align-items-center">
              <div class="flex-wrap d-flex justify-content-between flex-grow-1">
                <div>
                  <h6 class="mb-0">Internet Explorer</h6>
                </div>
                <div class="gap-2 user-progress d-flex align-items-center">
                  <h6 class="mb-0">62.2%</h6>
                </div>
              </div>
              <div class="chart-progress" data-color="info" data-series="60"></div>
            </div>
          </li>
          <li class="d-flex align-items-center">
            <img src="{{asset('assets/img/icons/brands/brave.png')}}" alt="Brave" height="28" class="rounded me-3">
            <div class="gap-2 d-flex w-100 align-items-center">
              <div class="flex-wrap d-flex justify-content-between flex-grow-1">
                <div>
                  <h6 class="mb-0">Brave</h6>
                </div>
                <div class="gap-2 user-progress d-flex align-items-center">
                  <h6 class="mb-0">46.3%</h6>
                </div>
              </div>
              <div class="chart-progress" data-color="warning" data-series="45"></div>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </div>

@endsection
