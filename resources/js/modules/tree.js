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
    const $refreshTree = $('#moduleTreeRefresh');
    const $instance = $('#instance');
    const $instanceCreate = $('#moduleInstanceCreate');
    let activeModuleNode = null;

    try {
        window.localStorage.removeItem('modules:default');
    } catch (error) {
        // Ignore localStorage access issues in restricted environments.
    }

    function setExpandButtonState(isExpanded) {
        const iconClass = isExpanded ? 'fas fa-compress-arrows-alt' : 'fas fa-expand-arrows-alt';
        const label = isExpanded ? 'Collapse all' : 'Expand all';

        $expandAll.attr('data-expanded', isExpanded ? 'true' : 'false');
        $expandAll.attr('aria-pressed', isExpanded ? 'true' : 'false');
        $expandAll.find('i').attr('class', iconClass);
        $expandAll.find('span').text(label);
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderModulePanel(node, state) {
        if (!$instance.length) {
            return;
        }

        if (!node || state === 'empty') {
            activeModuleNode = null;
            $instance.html(
                "<div class='app-module-instance-empty'>" +
                "<h3 class='app-module-instance-heading'>No module selected</h3>" +
                "<p class='app-module-instance-copy'>Choose a module on the left, then click Create instance.</p>" +
                "<button type='button' id='moduleInstanceCreate' class='btn btn-primary' disabled><i class='fas fa-table'></i>Create instance</button>" +
                "</div>"
            );
            return;
        }

        const title = escapeHtml(node.text || 'Selected module');
        let badge = "<span class='app-module-instance-badge'>Selected module</span>";
        let message = 'The instance setup modal is ready. Choose the reporting date and available groups.';
        let buttonLabel = 'Create instance';

        if (state === 'loading') {
            badge = "<span class='app-module-instance-badge app-module-instance-badge-muted'>Loading</span>";
            message = 'Loading groups for this module.';
            buttonLabel = 'Loading...';
        } else if (state === 'error') {
            badge = "<span class='app-module-instance-badge app-module-instance-badge-danger'>Unavailable</span>";
            message = 'The module groups could not be loaded. Try again or open another module.';
        }

        const disabled = state === 'loading' ? 'disabled' : '';

        $instance.html(
            "<div class='app-module-instance-card'>" +
            badge +
            "<h3 class='app-module-instance-heading'>" + title + "</h3>" +
            "<p class='app-module-instance-copy'>" + escapeHtml(message) + "</p>" +
            "<button type='button' id='moduleInstanceCreate' class='btn btn-primary' " + disabled + ">" +
            "<i class='fas fa-table'></i>" + escapeHtml(buttonLabel) +
            "</button>" +
            "</div>"
        );
    }

    function populateModuleOptions(response) {
        const options = [];

        for (const key in response) {
            if (!Object.prototype.hasOwnProperty.call(response, key)) {
                continue;
            }

            let groupValue = response[key];

            if (typeof groupValue === 'string') {
                try {
                    groupValue = JSON.parse(groupValue);
                } catch (error) {
                    continue;
                }
            }

            if (!groupValue || typeof groupValue !== 'object' || Array.isArray(groupValue)) {
                continue;
            }

            const payload = {};
            payload[key] = groupValue;

            options.push(
                $('<option>', {
                    value: JSON.stringify(payload),
                    text: key
                })
            );
        }

        $('#multiselect option').remove();
        $('#multiselect_to option').remove();
        $('#multiselect').append(options);
    }

    function openModuleInstance(node, showModal) {
        if (!node || node.type !== 'mod') {
            return;
        }

        activeModuleNode = node;
        renderModulePanel(node, 'loading');

        $.ajax({
            url: 'modules/group',
            type: 'post',
            data: {
                module: node.original.mod,
                module_name: node.text
            }
        }).done(function (response) {
            if (typeof window.initModulePicker === 'function') {
                window.initModulePicker();
            }

            $('#module_name').val(node.text);
            $('#module_path').val(node.original.mod);

            populateModuleOptions(response);
            renderModulePanel(node, 'ready');

            if (showModal) {
                window.appModal.show('module');
            }
        }).fail(function () {
            renderModulePanel(node, 'error');
        });
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

    function isBranchFullyExpanded(instance, node) {
        if (!instance || !node) {
            return false;
        }

        const branchNode = instance.get_node(node);

        if (!branchNode || !Array.isArray(branchNode.children) || branchNode.children.length === 0) {
            return false;
        }

        if (!instance.is_open(branchNode.id)) {
            return false;
        }

        const branchIds = [branchNode.id].concat(Array.isArray(branchNode.children_d) ? branchNode.children_d : []);

        return branchIds.every(function (branchId) {
            const currentNode = instance.get_node(branchId);

            if (!currentNode || !Array.isArray(currentNode.children) || currentNode.children.length === 0) {
                return true;
            }

            return instance.is_open(branchId);
        });
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
        plugins: ['contextmenu', 'search', 'types', 'wholerow'],
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
                const instance = $tree.jstree(true);
                const canToggleBranch = node.type !== 'file' && Array.isArray(node.children) && node.children.length > 0;
                const branchExpanded = canToggleBranch ? isBranchFullyExpanded(instance, node) : false;

                return {
                    ToggleBranch: {
                        separator_before: false,
                        separator_after: true,
                        label: branchExpanded ? 'Collapse' : 'Expand',
                        _disabled: !canToggleBranch,
                        icon: branchExpanded ? 'fas fa-compress-arrows-alt' : 'fas fa-expand-arrows-alt',
                        action: function () {
                            const currentInstance = $tree.jstree(true);

                            if (!currentInstance) {
                                return;
                            }

                            if (isBranchFullyExpanded(currentInstance, node)) {
                                currentInstance.close_all(node);
                            } else {
                                currentInstance.open_all(node);
                            }

                            syncExpandButton();
                        }
                    },
                    Create: {
                        separator_before: false,
                        separator_after: false,
                        label: 'New instance',
                        _disabled: node.type !== 'mod',
                        icon: 'fas fa-external-link-alt',
                        action: function () {
                            openModuleInstance(node, true);
                        }
                    }
                };
            }
        }
    }).on('ready.jstree open_node.jstree close_node.jstree refresh.jstree', function () {
        syncExpandButton();
    }).on('select_node.jstree', function (event, data) {
        if (data.node.type === 'mod') {
            openModuleInstance(data.node, false);
            return;
        }

        if (data.node.type === 'file' && activeModuleNode) {
            renderModulePanel(activeModuleNode, 'ready');
            return;
        }

        renderModulePanel(null, 'empty');
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

    $refreshTree.on('click', function () {
        const instance = $tree.jstree(true);

        if (!instance) {
            return;
        }

        $search.val('');
        renderModulePanel(null, 'empty');
        instance.refresh();
    });

    $(document).on('click', '#moduleInstanceCreate', function () {
        if (activeModuleNode) {
            openModuleInstance(activeModuleNode, true);
        }
    });

    renderModulePanel(null, 'empty');
    setExpandButtonState(false);
}
