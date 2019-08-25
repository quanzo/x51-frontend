<?php

return array(
	'number'=>function ($arg) {
		/*foreach ($arg as $key => $val)
		{
			echo "val=".scss_getValue($val).' unit='.scss_getUnit($val)."\r\n<br>";
		}*/
		/*reset($arg);
		return (float)current($arg);*/
		
		return array(
			0=>'number',
			1=>scss_getValue(current($arg)),
			2=>scss_getUnit(current($arg))
		);
	},
	'FRONTEND_PATH'=>function ($arg) {
		if ($arg)
		{
			$fp=scss_getValue(current($arg));
		} else $fp='';
		if (defined('FRONTEND_PATH')) return FRONTEND_PATH.$fp;
		return '/'.$fp;
	}
);

function scss_getValue(array $param) {
	if ($param[0]=='keyword' || $param[0]=='number')
	{
		return $param[1];
	}
	if ($param[0]=='string') {
		if (count($param[2])==1 && !is_array($param[2][0]))
		{
			return $param[2][0];
		}
		else
		{
			return $param[2][0][1];
		}
	}
} // end scss_getValue

function scss_getUnit(array $param)
{
	if ($param[0]=='number')
	{
		return $param[2];
	}
	if ($param[0]=='string') {
		if (count($param[2])>1 && is_array($param[2][0]))
		{
			return $param[2][1];
		}
	}
	return '';
}

/*
Array
(
    [0] => Array
        (
            [0] => string
            [1] => 
            [2] => Array
                (
                    [0] => Array
                        (
                            [0] => number
                            [1] => 11.357142857143
                            [2] => 
                        )

                    [1] => em
                )

        )

    [1] => Array
        (
            [0] => keyword
            [1] => background
        )

    [2] => Array
        (
            [0] => string
            [1] => "
            [2] => Array
                (
                    [0] => textual
                )

        )

)
Array
(
    [0] => Array
        (
            [0] => number
            [1] => 1
            [2] => em
        )

)

*/