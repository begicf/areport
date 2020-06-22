<template>
    <div class="col-md-9" id="modules"></div>
</template>

<script>

    function getExtension($type) {

        switch ($type) {
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

    export default {
        name: "TreeComponent",
        mounted() {

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $(function () {
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
                                        'ext': (node.id === '#') ? 'fws' : getExtension(node.type),
                                        'mod': (typeof node.original === 'undefined') ? null : node.original.mod
                                    };
                                }
                            }
                        },
                        "types": {
                            "fws": {

                                "icon": "fas fa-folder text-primary",
                                "valid_children": ["group"]
                            },
                            "tax": {

                                "icon": "fa fa-box text-info",
                                "valid_children": ["group"]
                            },
                            "mod": {

                                "icon": "fa fa-box text-danger",
                                "valid_children": ["group"]
                            },
                            "group": {
                                "icon": "fa fa-layer-group text-primary",
                                "valid_children": ["file"]
                            },
                            "file": {
                                "icon": "fa fa-table text-success",
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
                                        "_disabled": ($node.type == 'mod') ? false : true,
                                        "icon": "fas fa-external-link-alt",
                                        "action": function () {


                                            $.ajax({
                                                url: 'modules/group',
                                                type: 'post',
                                                data: {
                                                    module: $node.original.mod,
                                                    module_name: $node.text
                                                },
                                            }).done(function (response) {

                                                $('#module_name').val($node.text);
                                                $('#module_path').val($node.original.mod);

                                                var optionsHTML = [];

                                                for (var k in response) {

                                                   git
                                                }

                                                $('#multiselect option').remove();
                                                $('#multiselect_to option').remove();


                                                $('#multiselect').append(optionsHTML);
                                                $('#module').modal();

                                            })


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
            })
        }
    }
</script>

<style scoped>

</style>
