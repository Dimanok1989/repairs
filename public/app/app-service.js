function Service() {

    /** Последний полученный идентификатор */
    this.last = 0;

    this.getWorkTape = () => {

        let data = {
            last: this.last,
            page: app.page,
        };

        $('#worktapeload').show();
        app.progress = true;

        app.ajax(`/api/token${app.token}/service/getWorkTape`, data, json => {

            $('#worktapeload').hide();

            app.page = json.data.next;
            app.progress = false;

            app.scrollDoit(this.getWorkTape);

            $.each(json.data.service, (i,row) => {
    
                let html = this.getHtmlRowService(row);    
                $('#worktape').append(html);

                if (this.last < row.id)
                    this.last = row.id;
    
            });

            if (json.data.next > json.data.last) {
                app.progressEnd = true;
                $('#worktape').append(`<small class="d-block my-2 opacity-40">Это все данные</small>`);
            }

        });

    }

    this.getHtmlRowService = row => {

        // Блок с фотографиями
        let images = '';

        $.each(row.imagesData, (i,img) => {

            app.fileList.push(img);

            let imgId = app.fileList.length - 1;

            images += `<div class="col mb-2 px-1 hover-link">
                <div class="card h-100" data-id="${imgId}" onclick="app.showImg(this);">
                    <div class="item-responsive item-16by9">
                        <div class="item-responsive-content"></div>
                        <img src="${img.link}" class="d-none img-fluid" alt="${img.name}" onload="$(this).removeClass('d-none');">
                    </div>
                </div>
            </div>`;
        });

        return `<div class="card my-3 text-left">
            <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <strong>${row.applicationData.bus} ${row.applicationData.clientName}</strong>
                    <small class="opacity-80">${row.dateAdd}</small>
                </div>
                <div>
                    <i class="fas ${row.projectIcon} opacity-60 mr-2"></i>
                    <a href="${row.applicationLink}" target="_blank">Заявка #${row.applicationId}</a>
                </div>
                <p class="my-0 font-weight-light">${row.usersList}</p>
                <p class="my-0 font-weight-light">${row.repairsList}</p>
                ${row.comment ? `<p class="mb-1 font-weight-light font-italic"><i class="fas fa-quote-left opacity-50 mr-2"></i>${row.comment}</p>` : ``}
                <div class="row row-cols-2 row-cols-md-3${images != "" ? ' mt-3' : ''} px-2">${images}</div>
            </div>
        </div>`;

    }

}
const service = new Service;