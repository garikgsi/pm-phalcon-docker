<?php
namespace Core\Lib;

class Math
{
    static public function sizeFilter( $bytes )
    {
        $label = array( 'B', 'KB', 'MB', 'GB');
        for ( $i = 0; $bytes >= 1024 && $i < ( count( $label ) -1 ); $bytes /= 1024, $i++ );
        return( round( $bytes, 2 ) . " " . $label[$i] );
    }
}