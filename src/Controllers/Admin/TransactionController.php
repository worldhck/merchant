<?php

namespace Arbory\Merchant\Controllers\Admin;

use App\Http\Controllers\Controller;
use Arbory\Base\Admin\Form;
use Arbory\Base\Admin\Form\Fields\Hidden;
use Arbory\Base\Admin\Grid;
use Arbory\Base\Admin\Traits\Crudify;
use Arbory\Merchant\Models\Transaction;
use Illuminate\Database\Eloquent\Model;

class TransactionController extends Controller
{
    use Crudify;

    /**
     * @var string
     */
    protected $resource = Transaction::class;

    /**
     * @return Form
     */
    protected function form(Model $model)
    {
        $form = $this->module()->form($model, function (Form $form) {
            $form->addField(new Hidden('id'));
        });

        return $form;
    }

    /**
     * @return Grid
     */
    public function grid()
    {
        return $this->module()->grid($this->resource(), function (Grid $grid) {
            $grid->column('tokenId');
            $grid->column('status');
        })->tools(['search']);
    }
}
