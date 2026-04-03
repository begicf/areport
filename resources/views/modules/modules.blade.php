@extends('layouts.app')

@section('content')
    <div class="app-page">
        <section class="app-page-header app-page-header-compact">
            <div class="app-page-header-row">
                <div class="app-page-header-main">
                    <div class="app-page-kicker mb-2">
                        <i class="fas fa-diagram-project"></i>
                        Module explorer
                    </div>
                    <h1 class="app-page-title">Browse the reporting taxonomy</h1>
                </div>

                <div class="app-page-header-meta">
                    <span class="text-uppercase small text-muted fw-semibold">Active taxonomy</span>
                    @if(!empty($activeTaxonomy))
                        <span class="badge rounded-pill text-bg-primary">{{ $activeTaxonomy->name }}</span>
                    @else
                        <span class="badge rounded-pill text-bg-secondary">Not set</span>
                    @endif
                    <a href="{{ url('/taxonomy/managing') }}" class="btn btn-sm btn-outline-secondary">
                        Manage taxonomy
                    </a>
                </div>
            </div>
        </section>

        @include('flash.flash-message')

        <section class="app-panel">
            <div class="app-tree-toolbar" role="toolbar" aria-label="Module tree tools">
                <div class="app-tree-search">
                    <label for="moduleTreeSearch" class="visually-hidden">Search modules</label>
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <input
                        id="moduleTreeSearch"
                        type="search"
                        class="form-control"
                        placeholder="Search"
                        autocomplete="off"
                    >
                </div>

                <button
                    type="button"
                    id="moduleTreeExpandAll"
                    class="btn btn-outline-secondary btn-sm app-tree-toggle-btn"
                    data-expanded="false"
                    aria-pressed="false"
                >
                    <i class="fas fa-expand-arrows-alt"></i>
                    <span>Expand all</span>
                </button>
            </div>

            <div
                id="modules"
                class="app-tree-shell"
                data-taxonomy-folder="{{ $activeTaxonomy->folder ?? 'default' }}"
            ></div>
        </section>
    </div>

    @include('components.module')
@endsection
