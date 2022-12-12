<style>
    /*** COLORS ***/
    /*** DEMO ***/
    .cntr {
        display: table;
        width: 100%;
        height: 100%;
    }

    .cntr .cntr-innr {
        display: table-cell;
        text-align: center;
        vertical-align: middle;
    }

    /*** STYLES ***/
    .search {
        display: inline-block;
        position: relative;
        height: 35px;
        width: 35px;
        box-sizing: border-box;
        margin: 0px 8px 7px 0px;
        padding: 7px 9px 0px 9px;
        border: 3px solid #FFFFFF;
        border-radius: 25px;
        transition: all 200ms ease;
        cursor: text;
    }

    .search:after {
        content: "";
        position: absolute;
        width: 3px;
        height: 20px;
        right: -5px;
        top: 21px;
        background: #FFFFFF;
        border-radius: 3px;
        transform: rotate(-45deg);
        transition: all 200ms ease;
    }

    .search.active,
    .search:hover {
        width: 200px;
        margin-right: 0px;
        height: 2.5em;
    }

    .search.active:after,
    .search:hover:after {
        height: 0px;
    }

    .search input {
        width: 100%;
        border: none;
        box-sizing: border-box;
        font-family: Helvetica, serif;
        font-size: 22px;
        color: inherit;
        background: transparent;
        outline-width: 0px;
        height: 20px;
    }

    input:focus, textarea:focus, select:focus {
        outline: none !important;
        border: none !important;
        box-shadow: none !important;
    }

    textarea:focus, input:focus {
        border: none !important;
        box-shadow: none !important;
    }

    *:focus {
        outline: none !important;
        box-shadow: none !important;
    }

    .display_search_table {
        max-width: 35%;
        min-width: 220px;
        display:block;
        position:absolute;
        float:right;
        z-index: 10;
        opacity:0.9;
    }

    .hide_search_table {
        display:none;
    }

    .cursor_pointer {
        cursor:pointer;
    }

</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/zepto/1.0/zepto.min.js"></script>
<div class="col-sm-1">
    <div class="cntr">
        <div class="cntr-innr">
            <label class="search" for="inpt_search">
                <input class="text-white" id="inpt_search" type="text" autocomplete="off"/>
            </label>
        </div>
    </div>

    <div id="search_results" class="row hide_search_table">
        <table class="table table-responsive table-dark table-sm text-center table-hover border border-warning">
            <thead>
            <tr>
                <th scope="col">Title</th>
                <th scope="col">Date</th>
            </tr>
            </thead>
            <tbody class="bg-dark" style="font-size: 17px;">

            </tbody>
        </table>
    </div>
</div>


<script>
    $("#inpt_search").on('focus', function () {
        $(this).parent('label').addClass('active');
    });

    $("#inpt_search").on('blur', function () {
        if ($(this).val().length == 0)
            $(this).parent('label').removeClass('active');
    });

    $(document).ready(function () {
        $('#inpt_search').on('keyup', function () {
            var query = $(this).val();

            var postData = {"search": query};

            if(query){
                document.getElementById("search_results").classList.remove('hide_search_table');
                document.getElementById("search_results").classList.add('display_search_table');
            } else {
                document.getElementById("search_results").classList.remove('display_search_table');
                document.getElementById("search_results").classList.add('hide_search_table');
            }
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                type: "POST",
                url: '/list/search/',
                data: postData,
                success: function (response) {

                    const table = document.getElementById("search_results");

                    $(table).find('tbody').empty();

                    if (response.status == 404) {
                        //TODO: display error message

                        $(table).find('tbody')
                            .append('<tr><td> No Data </td></tr>')
                    } else {
                        response.tasks.forEach(function(item, index, arr){
                            var itemsDate = item.date_due;

                            if(itemsDate){
                                $(table).find('tbody')
                                    .append('<tr class="border border-warning" id="' + item.id +'">')
                                    .append('<td class="cursor_pointer" onClick="getItemsDate(\'' + itemsDate + '\')">' + item.title + '</td>')
                                    .append('<td>' + item.date_due + '</td>')
                                    .append('</tr>');
                            }
                        })
                    }
                }
            });
        });
    });

    function getItemsDate(date_str){
        $( "#selected_week" ).datepicker("setDate",date_str);
        document.getElementById("custom_week").submit();
    }

    function padTo2Digits(num) {
        return num.toString().padStart(2, '0');
    }

    function formatDate(date) {
        return [
            padTo2Digits(date.getDate()),
            padTo2Digits(date.getMonth() + 1),
            date.getFullYear(),
        ].join('/');
    }

</script>

