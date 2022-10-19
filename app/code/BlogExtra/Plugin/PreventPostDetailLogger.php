<?php declare(strict_types=1);

namespace MaxiCompra\BlogExtra\Plugin;

use MaxiCompra\Blog\Observer\LogPostDetailView;

class PreventPostDetailLogger
{
    public function aroundExecute(
        LogPostDetailView $subject,
        callable $proceed)
        {
            
        }
    }