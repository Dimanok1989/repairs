<div class="mt-4 mb-4 mx-auto" style="max-width: 500px;">
    <div class="input-group">
        <input type="search" class="form-control" id="search-start" placeholder="Поиск..." aria-label="Поиск..." onkeyup="app.searchStart();" value="{{ request()->text ?? '' }}">
        <div class="input-group-append">
            <button class="btn btn-outline-secondary" type="button" onclick="app.searchStart(true);"><i class="fas fa-search"></i></button>
        </div>
    </div>
</div>