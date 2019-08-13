<?php
return $pattern = [
1=>'^\s*[第卷][\d一二三四五六七八九十零〇百千两]*[章回部节集卷].*$',
2=>'^(?<=\s)(?:第[\d零一二两三四五六七八九十百千万壹贰叁肆伍陆柒捌玖拾佰仟]+?(?:章|节[^课]|卷|集|部[^分]|篇|回[^头合])|简介|前言|序章|楔子|终章|后记|尾声|番外).*$',
3=>'^\s*?(?:第[\d零一二两三四五六七八九十百千万壹贰叁肆伍陆柒捌玖拾佰仟]+?(?:章|节[^课]|卷|集|部[^分]|篇|回[^头合])|简介|前言|序章|楔子|终章|后记|尾声|番外).*$',
4=>'^\s*(?:[Cc]hapter|[Ss]ection)\s*\d{1,5}.*$',//英文书籍 Chapter/Section 序号 标题1
5=>'\s*?正文\s*第?[\d零一二两三四五六七八九十百千万壹贰叁肆伍陆柒捌玖拾佰仟][^\n\r]*$',// 正文 序号
6=>'^\s{0,4}\d+.{0,16}$',// 序号 标题
];
?>