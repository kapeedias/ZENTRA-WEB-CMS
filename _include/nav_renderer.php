<?php

function renderMenu($items, $parentId = null)
{
    echo '<ul class="nav flex-column">';

    foreach ($items as $item) {
        if ($item['parent_id'] == $parentId) {

            $children = array_filter($items, fn($child) => $child['parent_id'] == $item['id']);

            echo ' <li class="nav-item">';

            if ($children) {
                echo '
                    <div><a class="btn btn-link text-decoration-none d-flex justify-content-between align-items-center px-3" data-bs-toggle="collapse" href="#menu' . $item['id'] . '">
                        <span><i class="fa fa-' . $item['icon'] . ' me-2"></i> ' . $item['title'] . '</span><i class="icon ion-chevron-right arrow"></i>
                    </a>
                    <div class="collapse" id="menu' . $item['id'] . '"><ul class="nav ms-3">';

                renderMenu($items, $item['id']);
                echo '</ul></div>';
            } else {
                echo '
                    <a class="nav-link" href="' . $item['url'] . '">
                        <i class="fa fa-' . $item['icon'] . '"></i> ' . $item['title'] . '
                    </a>';
            }

            echo '</li>';
        }
    }

    echo '</ul>';
}
