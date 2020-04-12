<template>
    <div class="col-md-9" id="modules"></div>
</template>

<script>

    export default {
        name: "TreeComponent",
        mounted() {

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $('#modules').jstree(
                {
                    'core': {
                        'check_callback': true,
                        'data': {
                            'animation': 0,
                            'type': "POST",
                            'dataType': "json",
                            'cache': false,
                            'themes': {
                                'responsive': true,
                                'name': 'proton'
                            },
                            'url': 'modules/json',

                            'data': function (node) {
                                return {
                                    'id': node.id,
                                    'path': node.data,
                                    'ext': (node.id === '#') ? 'fws' : (node.original.type == 'fws') ? "tax" : (node.original.type == 'tax') ? "mod" : "mod",
                                    'mod': (typeof node.original === 'undefined') ? null : node.original.mod
                                };
                            }
                        }
                    },
                    "types": {
                        "#": {
                            "max_children": 1,
                            "max_depth": 4,
                            "icon": "fa fa-box",
                            "valid_children": ["group"]
                        },
                        "group": {
                            "icon": "fa fa-layer-group",
                            "valid_children": ["file"]
                        },
                        "file": {
                            "icon": "fa fa-table",
                            "valid_children": []
                        }
                    },
                    "plugins": [
                        "contextmenu", "state", "types", "wholerow"
                    ],
                    "contextmenu": {
                        "items": function ($node) {

                            return {
                                "Create": {
                                    "separator_before": false,
                                    "separator_after": false,
                                    "label": "New instance",
                                    "_disabled": ($node.type == 'file') ? false : true,
                                    "icon": "fas fa-external-link-alt",
                                    "action": function () {

                                        $('#table').val($node.data);
                                        $('#lang').val($node.original.lang);
                                        $('#mod').val($node.original.mod);
                                        $('#ext_code').val($node.original.ext_code);
                                        $('#module').modal();

                                    }
                                },

                            }
                        }
                    }
                }).on('select_node.jstree', function (e, data, response) {

                if (data.node.type == '#') {

                    $.post('/ajax_instance',
                        {'mod': data.node.original.mod})
                        .fail(function () {
                            //data.instance.refresh();
                        })
                        .done(function (data) {

                            $("#instance").html(data);
                        });
                } else {
                    $("#instance").empty();
                }

            })
        }
    }
</script>

<style scoped>

</style>
