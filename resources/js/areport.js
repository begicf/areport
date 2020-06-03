$("#addRow").on('click', function () {

    let rowCount = $('#table tbody').find('tr').length;

    $('#table tbody>tr:last').clone(true).each(function () {

        $(this).find('td input, td select').each(function () {


            let name = $(this).attr('name').replace(/(c\d*r)\d*([^]*)/, "$1" + rowCount + "$2");
            let id = name.substring(0, name.indexOf('['));
            $(this).attr('name', name);
            if ($(this).attr('type') != 'hidden') {
                $(this).val('');
                $(this).attr('id', id);
            }
        });

    }).insertAfter('#table tbody>tr:last');

    rowCount++;
    return false;
});


$("#delRow").click(function () {

    let rowCount = $('#table tbody').find('tr').length;
    let $tbody = $("#table tbody");

    let $last = $tbody.find('tr:last');
    if ($last.is(':first-child')) {
        alert('You cannot delete the last one!');
    } else {
        $last.remove();
        rowCount--;
    }
});
