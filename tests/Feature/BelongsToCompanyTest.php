<?php

use App\Models\TestModel;

it('can use the BelongsToCompany trait', function () {
    $model = new TestModel();
    expect($model)->toBeInstanceOf(TestModel::class);
});
