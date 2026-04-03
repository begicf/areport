function getExtension(type) {
    switch (type) {
        case 'default':
            return 'fws';
        case 'fws':
            return 'tax';
        case 'tax':
            return 'mod';
        case 'mod':
            return 'tab';
        default:
            return 'tab';
    }
}

function debounce(callback, delay) {
    let timeoutId;

    return function (...args) {
        clearTimeout(timeoutId);
        timeoutId = window.setTimeout(function () {
            callback.apply(null, args);
        }, delay);
    };
}

export default function initTreeModule() {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    const $tree = $('#modules');
    const $search = $('#moduleTreeSearch');
    const $expandAll = $('#moduleTreeExpandAll');
    const taxonomyFolder = String($tree.data('taxonomy-folder') || 'default');

    function setExpandButtonState(isExpanded) {
        const iconClass = isExpanded ? 'fas fa-compress-arrows-alt' : 'fas fa-expand-arrows-alt';
        const label = isExpanded ? 'Collapse all' : 'Expand all';

        $expandAll.attr('data-expanded', isExpanded ? 'true' : 'false');
        $expandAll.attr('aria-pressed', isExpanded ? 'true' : 'false');
        $expandAll.find('i').attr('class', iconClass);
        $expandAll.find('span').text(label);
    }

    function hasOpenBranches(instance) {
        return instance.get_json('#', { flat: true }).some(function (node) {
            return node.id !== '#' && Array.isArray(node.children) && node.children.length > 0 && instance.is_open(node.id);
        });
    }

    function hasClosedBranches(instance) {
        return instance.get_json('#', { flat: true }).some(function (node) {
            return node.id !== '#' && Array.isArray(node.children) && node.children.length > 0 && !instance.is_open(node.id);
        });
    }

    function syncExpandButton() {
        const instance = $tree.jstree(true);

        if (!instance) {
            return;
        }

        setExpandButtonState(hasOpenBranches(instance) && !hasClosedBranches(instance));
    }

    $tree.jstree({
        core: {
            check_callback: true,
            data: {
                animation: 0,
                type: 'POST',
                dataType: 'json',
                cache: false,
                themes: {
                    responsive: true,
                    name: 'proton'
                },
                url: 'modules/json',
                data: function (node) {
                    return {
                        id: node.id,
                        path: node.data,
                        ext: node.id === '#' ? 'fws' : getExtension(node.type),
                        mod: typeof node.original === 'undefined' ? null : node.original.mod
                    };
                }
            }
        },
        state: {
            key: `modules:${taxonomyFolder}`
        },
        types: {
            fws: {
                icon: 'fas fa-folder text-primary',
                valid_children: ['group']
            },
            tax: {
                icon: 'fa fa-box text-info',
                valid_children: ['group']
            },
            mod: {
                icon: 'fa fa-box text-danger',
                valid_children: ['group']
            },
            group: {
                icon: 'fa fa-layer-group text-primary',
                valid_children: ['file']
            },
            file: {
                icon: 'fa fa-table text-success',
                valid_children: []
            }
        },
        plugins: ['contextmenu', 'search', 'state', 'types', 'wholerow'],
        search: {
            show_only_matches: true,
            show_only_matches_children: true,
            case_sensitive: false,
            search_callback: function (searchTerm, node) {
                const query = String(searchTerm || '').trim().toLowerCase();

                if (!query) {
                    return true;
                }

                const searchableParts = [
                    node.text,
                    node.id,
                    typeof node.original === 'undefined' ? '' : node.original.mod,
                    typeof node.original === 'undefined' ? '' : node.original.path
                ];

                return searchableParts.some(function (part) {
                    return String(part || '').toLowerCase().includes(query);
                });
            }
        },
        contextmenu: {
            items: function (node) {
                return {
                    Create: {
                        separator_before: false,
                        separator_after: false,
                        label: 'New instance',
                        _disabled: node.type !== 'mod',
                        icon: 'fas fa-external-link-alt',
                        action: function () {
                            $.ajax({
                                url: 'modules/group',
                                type: 'post',
                                data: {
                                    module: node.original.mod,
                                    module_name: node.text
                                }
                            }).done(function (response) {
                                const optionsHTML = [];

                                $('#module_name').val(node.text);
                                $('#module_path').val(node.original.mod);

                                for (const key in response) {
                                    optionsHTML.push('<option value={"' + key + '":' + response[key] + '}>' + key + '</option>');
                                }

                                $('#multiselect option').remove();
                                $('#multiselect_to option').remove();
                                $('#multiselect').append(optionsHTML);
                                window.appModal.show('module');
                            });
                        }
                    }
                };
            }
        }
    }).on('ready.jstree open_node.jstree close_node.jstree refresh.jstree', function () {
        syncExpandButton();
    }).on('select_node.jstree', function (event, data) {
        if (data.node.type === '#') {
            $.post('/ajax_instance', {
                mod: data.node.original.mod
            }).done(function (response) {
                $('#instance').html(response);
            });
        } else {
            $('#instance').empty();
        }
    });

    const runSearch = debounce(function (value) {
        $tree.jstree(true).search(value);
    }, 180);

    $search.on('input', function () {
        runSearch($(this).val());
    });

    $expandAll.on('click', function () {
        const instance = $tree.jstree(true);
        const shouldCollapse = hasOpenBranches(instance) && !hasClosedBranches(instance);

        if (shouldCollapse) {
            instance.close_all();
            setExpandButtonState(false);
            return;
        }

        instance.open_all();
        setExpandButtonState(true);
    });

    setExpandButtonState(false);
}
