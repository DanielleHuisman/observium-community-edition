<?php

//define('OBS_DEBUG', 2);

include(dirname(__FILE__) . '/../includes/sql-config.inc.php');
//include(dirname(__FILE__) . '/../includes/defaults.inc.php');
//include(dirname(__FILE__) . '/../config.php');
//include(dirname(__FILE__) . '/../includes/definitions.inc.php');
//include(dirname(__FILE__) . '/../includes/functions.inc.php');

class IncludesEntitiesTest extends \PHPUnit\Framework\TestCase
{

  /**
  * @dataProvider providerEntityDescrDefinition
  * @group descr
  */
  public function testEntityDescrDefinition($type, $result, $definition, $descr_entry, $count = 1)
  {
    $this->assertSame($result, entity_descr_definition($type, $definition, $descr_entry, $count));
  }


  public function providerEntityDescrDefinition()
  {
    $result = array();

    // Mempool
    $type = 'mempool';
    $definition = array();
    $array      = array('i' => '22', 'index' => '33');

    // Defaults from entity definition
    $result[] = array($type, 'Memory',          $definition, $array);
    $result[] = array($type, 'Memory Pool 33',  $definition, $array, 2);

    // Descr from oid_descr, but it empty
    $definition['oid_descr'] = 'OidName';
    $result[] = array($type, 'Memory',          $definition, $array);
    // Descr from descr
    $definition['descr'] = 'Name from Descr';
    $result[] = array($type, 'Name from Descr', $definition, $array);
    $result[] = array($type, 'Name from Descr 33', $definition, $array, 2);
    // Descr from oid_descr
    $array['OidName'] = 'Name from Oid';
    $result[] = array($type, 'Name from Oid',   $definition, $array);
    $result[] = array($type, 'Name from Oid',   $definition, $array, 2);
    // Now descr use tags
    $definition['descr'] = 'Name from Descr with Tags (%i%) {%index%} [%oid_descr%]';
    $result[] = array($type, 'Name from Descr with Tags (22) {33} [Name from Oid]', $definition, $array);
    $definition['descr'] = 'Name from Descr with Tags (%OidName%)';
    $result[] = array($type, 'Name from Descr with Tags (Name from Oid)', $definition, $array);
    // Tag multiple times
    $definition['descr'] = 'Name from Descr with multiple Tags {%oid_descr%} [%oid_descr%]';
    $result[] = array($type, 'Name from Descr with multiple Tags {Name from Oid} [Name from Oid]', $definition, $array);
    // Multipart indexes
    $definition['descr'] = 'Name from Descr with Tags {%index0%}';
    $result[] = array($type, 'Name from Descr with Tags {33}', $definition, $array);
    $array['index'] = '11.22.33.44.55';
    $definition['descr'] = 'Name from Descr with Multipart Index {%index1%} {%index3%} {%index2%} [%index%]';
    $result[] = array($type, 'Name from Descr with Multipart Index {22} {44} {33} [11.22.33.44.55]', $definition, $array);

    // Sensors
    $type = 'sensor';
    $definition = array();
    $array      = array('i' => '22', 'index' => '33');

    $definition['oid_descr'] = 'jnxOperatingDescr';
    $definition['descr_transform'] = ['action' => 'entity_name'];

    $array['jnxOperatingDescr'] = "PIC: 4x 10GE(LAN) SFP+     @ 0/0/*";
    $result[] = array($type, 'PIC: 4x 10GE(LAN) SFP+ @ 0/0/*',   $definition, $array);

    return $result;
  }

}

// EOF
