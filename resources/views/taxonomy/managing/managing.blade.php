@extends('layouts.app')

@section('content')
    <div class="app-page">
        <section class="app-page-header">
            <div class="app-page-kicker">
                <i class="fas fa-sitemap"></i>
                Taxonomy management
            </div>
            <h1 class="app-page-title">Manage uploaded taxonomies</h1>
            <p class="app-page-copy">
                Select the active taxonomy for reporting and remove unused packages from the workspace.
            </p>
        </section>

        @include('flash.flash-message')

        <section class="app-panel">
            <form method="post" class="app-form-stack">
                @csrf

                <div class="app-table-shell">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th>Active</th>
                            <th>Display name</th>
                            <th>Original package</th>
                            <th class="text-center">Remove</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($tax as $row)
                            <tr>
                                <td class="text-center">
                                    <div class="form-check d-inline-flex align-items-center justify-content-center">
                                        <input class="form-check-input" type="radio" name="tax_active"
                                               id="tax_active_{{ $row->id }}"
                                               value="{{ $row->id }}" {{ $row->active == true ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td>
                                    <label class="form-check-label fw-semibold mb-0" for="tax_active_{{ $row->id }}">
                                        {{ $row->name }}
                                    </label>
                                </td>
                                <td>{{ $row->original_name }}</td>
                                <td class="text-center">
                                    <button onclick='deleteTaxonomy(@json($row))' {{ $row->active == true ? 'disabled' : '' }}
                                            type="button" class="btn btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="app-actions justify-content-end">
                    <button class="btn btn-primary px-4" type="submit">
                        <i class="fas fa-check"></i>
                        Set active taxonomy
                    </button>
                </div>
            </form>
        </section>
    </div>

    @include('components.please-wait')

    <script type="text/javascript">
        function deleteTaxonomy(selectedTax) {
            window.appModal.show('pleaseWaitDialog');

            axios.post('/taxonomy/delete', {
                id: selectedTax.id
            }).then(function () {
                window.location.reload();
            }).catch(function () {
                window.appModal.hide('pleaseWaitDialog');
            });
        }
    </script>
@endsection
