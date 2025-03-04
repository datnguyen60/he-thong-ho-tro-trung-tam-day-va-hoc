<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Automatically generated strings for Moodle installer
 *
 * Do not edit this file manually! It contains just a subset of strings
 * needed during the very first steps of installation. This file was
 * generated automatically by export-installer.php (which is part of AMOS
 * {@link http://docs.moodle.org/dev/Languages/AMOS}) using the
 * list of strings defined in /install/stringnames.txt.
 *
 * @package   installer
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['admindirname'] = '管理目录';
$string['availablelangs'] = '可用语言包';
$string['chooselanguagehead'] = '选择一种语言';
$string['chooselanguagesub'] = '请选择在安装过程中使用的语言。这个语言也会成为网站的缺省语言，不过以后可以随时更改。';
$string['clialreadyconfigured'] = '配置文件 config.php 已存在。请使用 admin/cli/install_database.php 为此站点安装 Moodle。';
$string['clialreadyinstalled'] = '配置文件 config.php 已存在。请使用 admin/cli/install_database.php 升级此站点的 Moodle。';
$string['cliinstallheader'] = 'Moodle {$a}命令行安装程序';
$string['clitablesexist'] = '数据库表已经存在，命令行安装无法继续。';
$string['databasehost'] = '数据库主机';
$string['databasename'] = '数据库名';
$string['databasetypehead'] = '选择数据库驱动';
$string['dataroot'] = '数据目录';
$string['datarootpermission'] = '数据目录权限';
$string['dbprefix'] = '表格名称前缀';
$string['dirroot'] = 'Moodle 目录';
$string['environmenthead'] = '检测您的运行环境...';
$string['environmentsub2'] = '每个Moodle的发行版都有一些对PHP版本的最低要求和几个必须安装的PHP扩展。在每次安装和升级前会做完整的环境检查。如果您不知道如何安装新版或启用PHP扩展，请与服务器管理员联系。';
$string['errorsinenvironment'] = '环境检查失败！';
$string['installation'] = '安装';
$string['langdownloaderror'] = '很不幸，无法下载“{$a}”语言包。安装过程将以英文继续。';
$string['memorylimithelp'] = '<p>您服务器的PHP内存限制是{$a}。</p>

<p>这会使Moodle在将来运行是碰到内存问题，特别是您安装了很多模块并且/或者有很多用户。</p>

<p>我们建议可能的话把限制设定的高一些，譬如40M。有几种方法可以做到这一点：</p>
<ol>
<li>如果可以，重新编译PHP并使用<i>--enable-memory-limit</i>选项。这允许Moodle自己设定内存限制。</li>
<li>如果可以访问php.ini文件，您可以修改<b>memory_limit</b>的设置为其它值，如40M。如果您无法访问，可以让您的管理员帮您修改一下。</li>
<li>在一些PHP服务器上，您可以在Moodle目录中创建一个.htaccess文件并包含如下内容:
<blockquote>php_value memory_limit 40M</blockquote>
<p>然而，在一些服务器上这会让<b>所有</b>PHP页面无法正常工作(在访问页面时会有错误)，因此您可能不得不删除.htaccess文件。</p></li>
</ol>';
$string['paths'] = '路径';
$string['pathserrcreatedataroot'] = '安装程序无法建立数据目录({$a->dataroot})。';
$string['pathshead'] = '确认路径';
$string['pathsrodataroot'] = '数据目录不可写';
$string['pathsroparentdataroot'] = '父目录({$a->parent})不可写。安装程序无法建立数据目录({$a->dataroot})。';
$string['pathssubadmindir'] = '有些网络主机使用/admin这个URL来访问控制面板或其它功能。很不幸，这个设置和Moodle管理页面的标准路径冲突。这个问题可以解决，只需在您的安装目录中把admin目录换名，然后把新名字输入到这里。例如<em>moodleadmin</em>。这么做会改变Moodle中的管理链接。';
$string['pathssubdataroot'] = '<p>Moodle 将在其中存储用户上传的所有文件内容的目录。</p>
<p>此目录应可由 Web 服务器用户（通常为 \'www-data\'、\'nobody\' 或 \'apache\'）读取和写入。</p>
<p>它不能通过 Web 直接访问。</p>
<p>如果该目录当前不存在，则在安装过程中将尝试创建该目录。</p>';
$string['pathssubdirroot'] = '<p>包含 Moodle 代码的目录的完整路径。</p>';
$string['pathssubwwwroot'] = '<p>将访问 Moodle 的完整地址，即用户在浏览器的地址栏中输入以访问Moodle 的地址。</p>
<p>无法使用多个地址访问 Moodle。如果您的网站可以通过多个地址访问，请选择最简单的地址并为其他每个地址设置永久重定向。</p>
<p>如果您的网站可以从 Internet 和内部网络（有时称为 Intranet）访问，请使用此处的公共地址。</p>
<p>如果当前地址不正确，请更改浏览器地址栏中的 URL 并重新开始安装。</p>';
$string['pathsunsecuredataroot'] = '数据目录所在位置不安全';
$string['pathswrongadmindir'] = '管理目录不存在';
$string['phpextension'] = '{$a} PHP扩展';
$string['phpversion'] = 'PHP版本';
$string['phpversionhelp'] = '<p>Moodle 需要至少 5.6.5 或 7.1 的 PHP 版本（7.0.x 有一些引擎限制）。</p>
<p>您当前运行的是版本 {$a}。</p>
<p>您必须升级 PHP 或移动到具有较新 PHP 版本的主机。</p>';
$string['welcomep10'] = '{$a->installername} ({$a->installerversion})';
$string['welcomep20'] = '您看到这个页面表明您已经成功地在您的计算机上安装并启用了<strong>{$a->packname} {$a->packversion}</strong>软件包。恭喜您！';
$string['welcomep30'] = '<strong>{$a->installername}</strong>的此发行版包含了可以创建<strong>Moodle</strong>运行环境的应用程序：';
$string['welcomep40'] = '这个软件包还包含了<strong>Moodle {$a->moodlerelease} ({$a->moodleversion})</strong>。';
$string['welcomep50'] = '此软件包中所有应用程序的使用均受其各自许可证的约束。完整的 <strong>{$a->installername}</strong> 软件包是 <a href=“https://www.opensource.org/docs/definition_plain.html”>开源的，</a>并在 <a href=“https://www.gnu.org/copyleft/gpl.html”>GPL 许可证下分发</a>';
$string['welcomep60'] = '接下来的页面会引导您通过一系列步骤在您的计算机上安装并配置好<strong>Moodle</strong>。您可以接受缺省的设置，或者根据需要修改它们。';
$string['welcomep70'] = '点击“向后”按钮以继续<strong>Moodle</strong>的安装过程。';
$string['wwwroot'] = '网站地址';
