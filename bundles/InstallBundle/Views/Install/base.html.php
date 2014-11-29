<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view['assets']->addScriptDeclaration("var MauticInstaller = {
    showWaitMessage: function(event) {
        event.preventDefault();

        if (mQuery('#waitMessage').length) {
            mQuery('#stepNavigation').addClass('hide');
            mQuery('#waitMessage').removeClass('hide');
        }

        mQuery('.btn-next').prop('disabled', true);
        mQuery('.btn-next').html('<i class=\"fa fa-spin fa-spinner fa-fw\"></i>{$view['translator']->trans('mautic.install.please.wait')}');

        setTimeout(function() {
            mQuery('form').submit();
        }, 10);
    }
};");

?>
<!DOCTYPE html>
<html>
    <?php echo $view->render('MauticCoreBundle:Default:head.html.php'); ?>
    <body>
        <!-- start: app-wrapper -->
        <section id="app-wrapper">
            <div class="container">
                <div class="row mt-20">
                    <div class="text-center">
                        <img src="<?php echo $view['assets']->getUrl('media/images/mautic_logo_lb200.png') ?>" height="50px" />
                        <h5 class="semibold text-muted mt-5"><?php $view['slots']->output('header', ''); ?></h5>
                    </div>

                    <div class="mt-20 col-lg-6 col-lg-offset-3">
                        <div id="app-content" class="panel">
                            <?php $view['slots']->output('_content'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!--/ end: app-content -->
    </body>
</html>
