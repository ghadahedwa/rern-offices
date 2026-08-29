<?php

namespace App\Http\Controllers;

use App\Models\ItemCategory;
use App\Models\Warehouse;
use App\Reports\CategoryStatement;
use App\Support\WarehouseScope;
use App\Reports\StatementLayout;
use Illuminate\Http\Request;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

/**
 * «بيان بأرصدة {القسم}» مطبوعاً — يقرأ فلترَي الشاشة من الـquery string.
 *
 * ⚠️ الحارس هنا **فوق** حارس الشاشة، لا بدلاً منه: الرابط يُفتح في تبويب
 *    مستقل وقد يُنسخ ويُحفظ، فيصل الطلب بلا مرور بـmount.
 */
class WarehouseCategoryStatementPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        abort_unless($user?->can('warehouses.export'), 403);

        // ⚠️ المعرّفان من الرابط — غير الرقمي يُرفض ٤٠٤ ولا يُمرَّر للاستعلام
        $warehouseId = (string) $request->query('wh', '');
        $categoryId  = (string) $request->query('category', '');
        abort_unless(ctype_digit($warehouseId) && ctype_digit($categoryId), 404);

        // ⚠️ والنطاق في الكنترولر أيضاً: الرابط يُفتح منسوخاً في طلبٍ مستقل
        //    عن الشاشة، والورقة تخرج من النظام موقَّعةً ومختومة
        $warehouse = WarehouseScope::apply(Warehouse::query(), 'warehouses.id', $user)
            ->findOrFail((int) $warehouseId);
        $category  = ItemCategory::findOrFail((int) $categoryId);

        $statement = CategoryStatement::build($warehouse, $category);

        // ⚠️ التخطيط يُجرَّب لا يُقدَّر: كل محاولة تبني الملف كاملاً وتعدّ صفحاته،
        //    ويُعتمد أول تخطيط يدخل في أوراقه. الأقسام الصغيرة تنجح من المحاولة
        //    الأولى، وأكبر قسم في البيانات (١٤٦ صنفاً) يقف عند الرابعة.
        //    والملف المعتمد هو ملف المحاولة الناجحة نفسها — لا يُبنى مرتين.
        $rendered = null;

        StatementLayout::fit(
            $statement['rows']->count(),
            function (array $layout) use ($statement, &$rendered) {
                $rendered = $this->render($statement, $layout);

                return $rendered->page;
            }
        );

        $filename = 'statement-'.$warehouse->id.'-'.$category->id.'-'.now()->format('Ymd-His').'.pdf';

        return response($rendered->Output('', 'S'), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'              => 'no-cache',
        ]);
    }

    /** يبني الملف بتخطيطٍ بعينه ويرجع كائن mpdf (فيه `page` = عدد الصفحات). */
    protected function render(array $statement, array $layout): Mpdf
    {
        $html = view('print.warehouse-category-statement-pdf', compact('statement', 'layout'))->render();

        $fontDirs = (new ConfigVariables())->getDefaults()['fontDir'];
        $fontData = (new FontVariables())->getDefaults()['fontdata'];

        $mpdf = new Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'default_font'  => 'dejavusans',
            'margin_top'    => 15,
            'margin_bottom' => 15,
            'margin_left'   => 15,
            'margin_right'  => 15,
            'fontDir'       => array_merge($fontDirs, [storage_path('fonts')]),
            'fontdata'      => $fontData,
            'tempDir'       => storage_path('mpdf'),
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->WriteHTML($html);

        return $mpdf;
    }
}
