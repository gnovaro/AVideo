<?php
require_once '../../../videos/configuration.php';
require_once $global['systemRootPath'] . 'objects/user.php';
require_once $global['systemRootPath'] . 'objects/functions.php';

if (!User::isAdmin()) {
    forbiddenPage("You can not do this");
    exit;
}

$_page = new Page(array('Pending Requests'));
?>
<style>
.bootgrid-table td{
    white-space: unset;
}
</style>
<div class="container">
    <div class="panel panel-default">
        <div class="panel-heading">
            <?php
            echo __("Pending Requests");
            ?>
        </div>
        <div class="panel-body">
            <div class="row bgWhite list-group-item">
                <table id="grid" class="table table-condensed table-hover table-striped">
                    <thead>
                        <tr>
                            <th data-column-id="user" data-width="150px"><?php echo __("User"); ?></th>
                            <th data-column-id="valueText" data-width="150px"><?php echo __("Value"); ?></th>
                            <th data-column-id="description" data-formatter="description"><?php echo __("Description"); ?></th>
                            <th data-column-id="status" data-formatter="status" data-width="150px"><?php echo __("Status"); ?></th>
                            <th data-column-id="created" data-order="desc" data-formatter="created" data-width="150px"><?php echo __("Date"); ?></th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {

        var grid = $("#grid").bootgrid({
            labels: {
                noResults: "<?php echo __("No results found!"); ?>",
                all: "<?php echo __("All"); ?>",
                infos: "<?php echo __("Showing {{ctx.start}} to {{ctx.end}} of {{ctx.total}} entries"); ?>",
                loading: "<?php echo __("Loading..."); ?>",
                refresh: "<?php echo __("Refresh"); ?>",
                search: "<?php echo __("Search"); ?>",
            },
            ajax: true,
            url: "<?php echo $global['webSiteRootURL']; ?>plugin/YPTWallet/view/pendingRequests.json.php",
            formatters: {
                "status": function(column, row) {
                    var status = "";
                    status = "<div class=\"btn-group\"><button class='btn btn-success btn-xs command-status-success'>Confirm</button>";
                    status += "<button class='btn btn-danger btn-xs command-status-canceled'>Cancel</button><div>";
                    return status;
                },
                "description": function(column, row) {
                    if (row.information) {
                        return row.information;
                    } else {
                        return row.description;
                    }
                },
                "created": function(column, row) {
                    return '<span class="pendingTimers">' + row.created + '</span>';
                }
            }
        }).on("loaded.rs.jquery.bootgrid", function() {

            /* Executes after data is loaded and rendered */
            grid.find(".command-status-success").on("click", function(e) {
                var row_index = $(this).closest('tr').index();
                var row = $("#grid").bootgrid("getCurrentRows")[row_index];
                setStatus("success", row.id);
            });

            grid.find(".command-status-canceled").on("click", function(e) {
                var row_index = $(this).closest('tr').index();
                var row = $("#grid").bootgrid("getCurrentRows")[row_index];
                setStatus("canceled", row.id);
            });
            createTimer('.pendingTimers');
        });
    });

    function setStatus(status, wallet_log_id) {
        modal.showPleaseWait();
        $.ajax({
            url: webSiteRootURL + 'plugin/YPTWallet/view/changeLogStatus.json.php',
            type: "POST",
            data: {
                status: status,
                wallet_log_id: wallet_log_id
            },
            success: function(response) {
                $(".walletBalance").text(response.walletBalance);
                modal.hidePleaseWait();
                if (response.error) {
                    setTimeout(function() {
                        avideoAlert("<?php echo __("Sorry!"); ?>", response.msg, "error");
                    }, 500);
                } else {
                    $("#grid").bootgrid("reload");
                }
            }
        });
    }
</script>
<?php
$_page->print();
?>
