@extends('index')

@section('title', 'Админка монтажа')

@section('content')

<h3 class="mt-4 mb-4">Монтаж</h3>

<div class="my-4 mx-auto text-center px-3" style="max-width: 500px;">
	<h5>Сформирвоать excel</h5>
	<div class="input-group mt-2">
  		<input type="date" class="form-control" value="{{ date("Y-m-01", time()-60*60*24*3) }}" id="start-excel">
  		<input type="date" class="form-control" value="{{ date("Y-m-t", time()-60*60*24*3) }}" id="stop-excel">
  		<div class="input-group-append">
			<button class="btn btn-outline-secondary" type="button" id="download-exel" onclick="montage.excel(this);"><i class="fas fa-file-excel" aria-hidden="true"></i></button>
  		</div>
	</div>
</div>

<table class="table table-sm mt-4 mx-auto table-adaptive" id="content-table" style="font-size: 80%; max-width: 1300px;">
    <thead class="thead-dark d-none">
        <tr>
            <th scope="col">#id</th>
            <th scope="col">Дата</th>
            <th scope="col">Машина</th>
            <th scope="col">Марка</th>
            <th scope="col">Гос. номер</th>
            <th scope="col">Филиал</th>
            <th scope="col">Площадка</th>
            <th scope="col">Завершил</th>
            <th scope="col"></th>
            <th scope="col"></th>
        </tr>
    </thead>
    <tbody id="all-montages">
    </tbody>
</table>

<div class="text-center" id="loading-table" style="display: none;">
    <div class="spinner-border" role="status">
        <span class="sr-only">Загрузка...</span>
    </div>
</div>

@endsection

@section('script')
<script>
    $(document).ready(() => {
        montage.allMontagesList(true);
    });
</script>
@endsection