<?php
namespace MineCloudvod\Integrations\Zibll;

class Zibll
{
    public function __construct()
    {
        add_filter('mcv_lms_is_enrolled', array($this, 'mcv_is_zibll_vip'));
        add_action('mcv_add_admin_options_before_purchase', array( $this, 'admin_options' ) );
    }

    public function mcv_is_zibll_vip($canplay){
        if( isset( MINECLOUDVOD_SETTINGS['zibll']['vip_free'] ) 
        && MINECLOUDVOD_SETTINGS['zibll']['vip_free'] 
        && function_exists( 'zib_get_user_vip_level' ) ){
            $vip_level = zib_get_user_vip_level();
            if( (isset(MINECLOUDVOD_SETTINGS['zibll']['vip_levels']) && is_array(MINECLOUDVOD_SETTINGS['zibll']['vip_levels']) && in_array( $vip_level, MINECLOUDVOD_SETTINGS['zibll']['vip_levels'] )) || (!isset(MINECLOUDVOD_SETTINGS['zibll']['vip_levels']) && $vip_level>0)){
                return 1;
            }
        }
        return $canplay;
    }

    public function admin_options(){
        $prefix = 'mcv_settings';
        \MCSF::createSection( $prefix, array(
            'id'    => 'mcv_zibll',
            'title' => 'Zibll',
            'icon'  => 'fa fa-circle',
            'fields' => array(
                array(
                    'id'        => 'zibll',
                    'type'      => 'fieldset',
                    'title'     => '',
                    'fields'    => array(
                        array(
                            'id'    => 'vip_free',
                            'type'  => 'switcher',
                            'title' => __('Vip can learn all courses for free', 'mine-cloudvod'),
                            'text_on'    => __('Enable', 'mine-cloudvod'),
                            'text_off'   => __('Disable', 'mine-cloudvod'),
                            'default' => false,
                        ),
                        array(
                            'id'    => 'vip_levels',
                            'title' => '会员等级',
                            'after' => '选中的会员等级，免费学习所有课程',
                            'type'  => 'select',
                            'options'     => [
                                '1' => '一级会员',
                                '2' => '二级会员',
                            ],
                            'multiple'      => true,
                            'attributes' => array(
                              'style'    => 'min-width: 150px;min-height:60px;'
                            ),
                            'dependency' => array('vip_free', '==', true),
                            'default'    => ['1','2']
                        ),
                    ),
                ),
            )
          ) );
    }
}
