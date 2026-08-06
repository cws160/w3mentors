<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$types = Category::getCategoriesTypes();
$yesNoArray = AppConstant::getYesNoArr();
$arrFlds = [
    'dragdrop' => '<i class="ion-arrow-move icon"></i>',
    'listserial' => Label::getLabel('LBL_Sr._No'),
    'cate_identifier' => Label::getLabel('LBL_IDENTIFIER'),
    'cate_name' => Label::getLabel('LBL_NAME'),
    'cate_sub_categories' => Label::getLabel('LBL_SUB_CATEGORIES'),
    'cate_records' => Label::getLabel('LBL_RECORDS'),
    'cate_featured' => Label::getLabel('LBL_FEATURED'),
    'cate_updated' => Label::getLabel('LBL_UPDATED'),
    'status' => Label::getLabel('LBL_STATUS'),
];
$width = [
    'dragdrop' => '5%',
    'listserial' => '5%',
    'cate_identifier' => '25%',
    'cate_name' => '30%',
    'cate_sub_categories' => '10%',
    'cate_records' => '5%',
    'cate_featured' => '5%',
    'cate_updated' => '10%',
    'status' => '5%',
];
if ($postedData['cate_type'] == Category::TYPE_QUESTION) {
    unset($arrFlds['cate_featured']);
    unset($width['cate_featured']);
}
if ($postedData['parent_id'] > 0) {
    unset($arrFlds['cate_sub_categories']);
    unset($width['cate_sub_categories']);
    unset($arrFlds['cate_featured']);
    unset($width['cate_featured']);
}
if (!$canEdit) {
    unset($arrFlds['dragdrop']);
    unset($width['dragdrop']);
} else {
    $arrFlds['action'] = Label::getLabel('LBL_ACTION');
    $width['action'] = '5%';
}
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table', 'id' => 'categoriesList']);
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arrFlds as $key => $val) {
    $e = $th->appendElement('th', ['width' => $width[$key]], $val, true);
}
$srNo = 0;
foreach ($arrListing as $sn => $row) {
    $srNo++;
    $tr = $tbl->appendElement('tr', ['id' => $row['cate_id']]);
    foreach ($arrFlds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'dragdrop':
                if ($row['cate_status'] == AppConstant::YES) {
                    $td->appendElement('i', ['class' => 'ion-arrow-move icon']);
                    $td->setAttribute("class", 'dragHandle');
                }
                break;
            case 'listserial':
                $td->appendElement('plaintext', [], $srNo);
                break;
            case 'cate_identifier':
            case 'cate_name':
                $td->appendElement('plaintext', [], CommonHelper::renderHtml($row[$key] ?? ''));
                break;
            case 'cate_updated':
                $td->appendElement('plaintext', [], MyDate::showDate($row[$key], true));
                break;
            case 'cate_featured':
                $td->appendElement('plaintext', [], $yesNoArray[$row['cate_featured']]);
                break;
            case 'cate_sub_categories':
                if ($row['cate_subcategories'] > 0) {
                    $action = ($postedData['cate_type'] == Category::TYPE_COURSE) ? 'index' : 'quiz';
                    $td->appendElement('a', ['href' => MyUtility::makeUrl('Categories', $action, [$row['cate_id']]), 'class' => 'link-text link-underline', 'title' => Label::getLabel('LBL_SUB_CATEGORIES')], $row['cate_subcategories'], true);
                } else {
                    $td->appendElement('plaintext', [], 0);
                }
                break;
            case 'cate_records':
                if ($row['cate_records'] > 0) {
                    if ($row['cate_type'] == Category::TYPE_QUESTION) {
                        if ($canViewQuestions) {
                            $qryString = '?ques_cate_id=' . $row['cate_id'];
                            if ($postedData['parent_id'] > 0) {
                                $qryString = '?ques_cate_id=' . $postedData['parent_id'] . '&ques_subcate_id=' . $row['cate_id'];
                            }
                            $td->appendElement('a', ['href' => MyUtility::makeUrl('Questions', 'index') . $qryString, 'class' => 'link-underline link-text', 'title' => Label::getLabel('LBL_QUESTIONS')], $row['cate_records'], true);
                        } else {
                            $td->appendElement('plaintext', ['title' => Label::getLabel('LBL_QUESTIONS')], $row['cate_records']);
                        }
                    } elseif ($row['cate_type'] == Category::TYPE_COURSE) {
                        if ($canViewCourses) {
                            $qryString = '?course_cateid=' . $row['cate_id'];
                            if ($postedData['parent_id'] > 0) {
                                $qryString = '?course_cateid=' . $postedData['parent_id'] . '&course_subcateid=' . $row['cate_id'];
                            }
                            $td->appendElement('a', ['href' => MyUtility::makeUrl('Courses', '') . $qryString, 'class' => 'link-text link-underline', 'title' => Label::getLabel('LBL_COURSES')], $row['cate_records'], true);
                        } else {
                            $td->appendElement('plaintext', ['title' => Label::getLabel('LBL_COURSES')], $row['cate_records']);
                        }
                    }
                } else {
                    $td->appendElement('plaintext', [], 0);
                }
                break;
            case 'status':
                $active = "active";
                if ($row['cate_status'] == AppConstant::NO) {
                    $active = 'inactive';
                }

                $statusClass = "";
                if ($canEdit === false) {
                    $statusClass = "disabled";
                }
                $str = '<label class="statustab ' . $active . '" ' . (($canEdit) ? 'onclick="updateStatus(\'' . $row['cate_id'] . '\', \'' . $row['cate_status'] . '\')"' : "") . '>
				  <span data-off="' . Label::getLabel('LBL_Active') . '" data-on="' . Label::getLabel('LBL_Inactive') . '" class="switch-labels "></span>
				  <span class="switch-handles '. $statusClass.'"></span>
				</label>';
                $td->appendElement('plaintext', [], $str, true);
                break;
            case 'action':
                $langId = !empty($row['catelang_lang_id']) ? $row['catelang_lang_id'] : 0;
                $action = new Action($row['cate_id']);
                if ($canEdit) {
                    $action->addEditBtn(Label::getLabel('LBL_EDIT'),  'categoryForm(' . $row['cate_id'] . ', "' . $langId . '")');
                    $action->addRemoveBtn(Label::getLabel('LBL_Delete'), 'remove("' . $row['cate_id'] . '")');
                }
                $td->appendElement('plaintext', ['class' => 'align-right'], $action->renderHtml(), true);
                break;
            default:
                $td->appendElement('plaintext', [], CommonHelper::renderHtml($row[$key] ?? '-'));
                break;
        }
    }
}

if (count($arrListing) == 0) {
    $tbl->appendElement('tr')->appendElement('td', ['colspan' => count($arrFlds)], Label::getLabel('LBL_NO_RECORDS_FOUND'));
}
echo $tbl->getHtml();
?>
<script>
    $(document).ready(function() {
        $('#categoriesList').tableDnD({
            onDrop: function(table, row) {
                updateOrder();
            },
            dragHandle: ".dragHandle",
        });
    });
</script>