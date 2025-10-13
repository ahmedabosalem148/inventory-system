<?php

return [
    'validation' => [
        'category_id' => [
            'required' => 'التصنيف مطلوب',
        ],
        'name' => [
            'required' => 'اسم المنتج مطلوب',
        ],
        'unit' => [
            'required' => 'وحدة القياس مطلوبة',
        ],
        'purchase_price' => [
            'required' => 'سعر الشراء مطلوب',
        ],
        'sale_price' => [
            'required' => 'سعر البيع مطلوب',
        ],
        'min_stock' => [
            'required' => 'الحد الأدنى للمخزون مطلوب',
        ],
    ],

    'messages' => [
        'created' => 'تم إضافة المنتج بنجاح',
        'updated' => 'تم تعديل المنتج بنجاح',
        'create_error' => 'حدث خطأ أثناء إضافة المنتج: :error',
        'delete_stock' => 'لا يمكن حذف المنتج. يوجد رصيد في المخزون: :qty وحدة',
        'delete_movements' => 'لا يمكن حذف المنتج. يوجد حركات مخزنية مسجلة عليه',
        'deleted' => 'تم حذف المنتج بنجاح',
        'delete_error' => 'حدث خطأ أثناء الحذف: :error',
    ],
];
