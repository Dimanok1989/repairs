<div class="modal" id="modal-application-act" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="modal-application-act-label" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-application-act-label">Акт</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form class="modal-body text-left">

                <div class="form-group">
                    <label for="asdu">Представитель заказчика</label>
                    <input type="text" class="form-control" id="asdu" name="asdu" aria-describedby="asduHelp">
                    <small id="asduHelp" class="form-text text-muted">Укажите полное ФИО представителя заказчика</small>
                </div>

                <div class="form-group">
                    <label for="engineer">Представитель исполнителя</label>
                    <select class="custom-select mr-sm-2" id="engineer" name="engineer"></select>
                </div>

                <div class="form-group">
                    <label for="remark">Выполненные работы</label>
                    <textarea class="form-control" id="remark" name="remark" rows="3"></textarea>
                </div>

                <input type="hidden" name="id" />

            </form>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="save-data" onclick="application.actSaveData(this);" disabled>Сохранить</button>
            </div>
        </div>
    </div>
</div>