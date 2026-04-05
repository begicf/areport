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

        <div class="app-modules-layout">
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

                    <div class="app-tree-actions">
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

                        <button
                            type="button"
                            id="moduleTreeRefresh"
                            class="btn btn-outline-secondary btn-sm app-tree-toggle-btn"
                        >
                            <i class="fas fa-rotate-right"></i>
                            <span>Refresh</span>
                        </button>
                    </div>
                </div>

                <div
                    id="modules"
                    class="app-tree-shell"
                    data-taxonomy-folder="{{ $activeTaxonomy->folder ?? 'default' }}"
                ></div>
            </section>

            <section class="app-panel app-module-instance-panel">
                <div class="app-panel-header">
                    <div>
                        <h2 class="app-panel-title">Create instance</h2>
                        <p class="app-panel-copy">Select a module from the tree to open the instance setup modal.</p>
                    </div>
                </div>

                <div id="instance" class="app-module-instance-state">
                    <div class="app-module-instance-empty">
                        <div class="app-module-instance-icon">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <h3 class="app-module-instance-heading">No module selected</h3>
                        <p class="app-module-instance-copy">
                            Choose a module on the left. The selected module details will appear here and the instance modal will open automatically.
                        </p>
                        <button type="button" id="moduleInstanceCreate" class="btn btn-primary" disabled>
                            <i class="fas fa-table"></i>
                            Create instance
                        </button>
                    </div>
                </div>
            </section>
        </div>
    </div>

    @include('components.module')
@endsection
