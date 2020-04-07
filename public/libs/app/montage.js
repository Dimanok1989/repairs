class Montage {

    
    openPage(e) {

        var data = {
            id: +$(e).data('page'),
        }

        console.log(data);

    }
    

}
const montage = new Montage;