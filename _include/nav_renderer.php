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
                    <div><a class="nav-link" data-bs-toggle="collapse" href="#menu' . $item['id'] . '">
                        <i class="fa fa-' . $item['icon'] . '"></i> ' . $item['title'] . '
                    </a>
                    <div class="collapse" id="menu' . $item['id'] . '">';
                renderMenu($items, $item['id']);
                echo '</div>';
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
