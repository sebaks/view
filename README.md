# View

ZF2 Module. Build template by config. Solution for reusing template blocks and code.

## Introduction

In our conception view contains 3 parts:
- Layout (General view style for several URI)
- Contents (One page template - for display one URI)
- Blocks (Some functional piece of view - many blocks for one URI)

Each part can contain this fields:
- `extend` - allow extend from other template, and override any other field
- `layout` - (make sense only in `contents`) - define layout for template
- `template` - template name
- `viewModel` - viewModel, which contain view logic and can ba accessed in template by `$this->viewModel()->getCurrent()`
- `variables` - any static variable, can set when config build, will be added to `ViewModel`
- `children` - other blocks, for rendering it anywhere in current template `$this->renderChildModel(‘child-name’)`

Config must be in key `sebaks-view`.

Each key in `contents` - route name.

## Example config

Some where in your `module.config.php`

```php
'sebaks-view' => [
    'layouts' => [
        'admin' => [
            'template' => 'layout/admin',
            'children' => [
                'top-panel' => 't4web-admin-top-panel',
                'sidebar-menu' => 't4web-admin-sidebar-menu',
            ],
        ]
    ],
    'contents' => [
        'admin-user-list' => [  // you project must contain route name admin-user-list
            'template' => 't4web-admin/index/index',
            'layout' => 'admin',
            'children' => [
                'filter' => 't4web-admin-list-filter',
                'table' => 't4web-admin-list-table',
                'paginator' => 't4web-admin-list-paginator',
            ],
            'variables' => [
                'title' => 'List of users',
            ],
        ]
    ],
    'blocks' => [
        't4web-admin-top-panel' => [
            'template' => 't4web-admin/top-panel',
        ],
        't4web-admin-sidebar-menu' => [
            'template' => 't4web-admin/sidebar-menu',
        ],
        't4web-admin-list-filter' => [
            'template' => 't4web-admin/list-filter',
        ],
        't4web-admin-list-table' => [
            'template' => 't4web-admin/list-table',
            'children' => [
                'table-head' => 't4web-admin-list-table-head',
                'table-row' => 't4web-admin-list-table-row',
            ],
        ],
        't4web-admin-list-table-head' => [
            'template' => 't4web-admin/list-table-head',
        ],
        't4web-admin-list-table-row' => [
            'template' => 't4web-admin/list-table-row',
        ],
        't4web-admin-list-paginator' => [
            'template' => 't4web-paginator/paginator',
        ],
    ],
],
```