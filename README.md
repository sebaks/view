# View

ZF2 Module. Build template by config. Solution for reusing template blocks and code.

## Introduction

In our conception view contains 3 parts:
- Layout (General view style for several URI)
- Contents (One page template - for display one URI)
- Blocks (Some functional piece of view - many blocks for one URI)

Each `block` contain:

```php
'admin-show-list' => [
  'extend' => 'admin-list',
  'layout' => 'admin-layout',
  'template' => 't4web-admin/content/list',
  'viewModel' => 'Shows\Action\Admin\Show\CreateAction\ListViewModel',
  'childrenDynamicLists' => [/*...*/],
  'data' => [/*...*/],
  'children' => [/*...*/],
],
```

if exists key `extend` - other key not required and will be inherited from parent view. Each key will be
merged with parent. You can describe some blocks one time and reuse it anywhere.

#### Block options

- `extend` - describe parent view, all config key will be inherited and merged with current config
- `layout` - define page layout name, it's can sense only for `contents` not `blocks` (because `layout` can 
  sense for whole route\uri)
- `template` - template name for current block
- `viewModel` - specific ViewModel for current block (it can hide complex view logic for current block)
- `childrenDynamicLists` - 
- `data` - array for variables in view. `data` can be `data['static']` and `data['fromGlobal']`. 
  - `data['static']` - static variables, for example labels, icons, some text etc.
  - `data['fromGlobal']` - dynamic data, wich can be fetched by controller.
- `children` - desribe child blocks, which describe as block and will be acessed like property in template:

  ```php
  <div class="box">
      <div class="box-body no-padding">
          <?= $this->table ?>
      </div>
      <div class="box-footer">
          <?= $this->paginator ?>
      </div>
  </div>
  ```
  
  in this case `table` and `paginator` will be render like child block.

Config must be in key `sebaks-view`.

Each key in `contents` - route name.

## Example config

Somewhere in your `module.config.php`

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
