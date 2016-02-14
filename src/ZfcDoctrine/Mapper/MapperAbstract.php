<?php

namespace ZfcDoctrine\Mapper;

use ZfcBase\Mapper\AbstractDbMapper;
use ZfcDB\Mapper\MapperInterface;
use Zend\Db\Metadata\Metadata;
use Doctrine\ORM\EntityManagerInterface;
use Zend\Stdlib\Hydrator\HydratorInterface;
use ZfcBase\Mapper\Exception\ExceptionInterface;

class MapperAbstract extends AbstractDbMapper implements MapperInterface {
	/**
	 *
	 * @var \Doctrine\ORM\EntityManagerInterface
	 */
	protected $entityManager;
	
	/**
	 * Columns or Fiels
	 *
	 * @var array
	 */
	protected $columns = null;
	
	/**
	 *
	 * @param EntityManagerInterface $entityManager        	
	 */
	public function __construct(EntityManagerInterface $entityManager) {
		$this->entityManager = $entityManager;
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see \ZfcDB\Mapper\MapperInterface::getColumns()
	 */
	public function getColumns() {
		if (! $this->columns) {
			$columns = $this->getHydrator ()->extract ( $this->getEntityPrototype () );
			
			$this->columns = array_keys ( $columns );
		}
		return $this->columns;
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see \ZfcBase\Mapper\AbstractDbMapper::getSelect()
	 */
	public function getSelect($table = null) {
		return parent::getSelect ( $table );
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see \ZfcBase\Mapper\AbstractDbMapper::getTableName()
	 */
	public function getTableName() {
		return $this->tableName;
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see \ZfcDB\Mapper\MapperInterface::save()
	 */
	public function save($object) {	    
		if (is_array ( $object )) {
			$object = $this->getHydrator ()->hydrate ( $object, $this->getEntityPrototype () );
		}
		
		if (! $object) {
			return;
		}
		
		$this->getEventManager()->trigger(__FUNCTION__, $this, array('entity' => $object));
		
		try{
    		$entityManager = $this->getEntityManager ();
    		$entityManager->persist ( $object );
    		$entityManager->flush ();
		}catch(\Exception $e){
		    $this->getEventManager()->trigger(__FUNCTION__.'.error', $this, array('entity' => $object,'exception' => $e));
		}
		
		$this->getEventManager()->trigger(__FUNCTION__.'.post', $this, array('entity' => $object));
		
		return $this;
	}
	
	/**
	 * (non-PHPdoc)
	 * 
	 * @see \ZfcDB\Mapper\MapperInterface::saveList()
	 */
	public function saveList(array $objectList) {
		$entityManager = $this->getEntityManager ();
		
		$this->getEventManager()->trigger(__FUNCTION__, $this, array('entityCollection' => $objectList));
		
		if (count ( $objectList )) {
		    try{
    			foreach ( $objectList as $object ) {
    				$entityManager->persist ( $object );
    			}
    			$entityManager->flush ();
			}catch(\Exception $e){
				$this->getEventManager()->trigger(__FUNCTION__.'.error', $this, array('entity' => $object, 'entityCollection' => $objectList, 'exception' => $e));
			}
		}
		
		$this->getEventManager()->trigger(__FUNCTION__.'.post', $this, array('entityCollection' => $objectList));
		
		return $this;
	}
	
	/**
	 * (non-PHPdoc)
	 * 
	 * @see \ZfcBase\Mapper\AbstractDbMapper::delete()
	 */
	public function delete($object, $tableName = null) {
		if (is_array ( $object )) {
			$object = $this->fetchOneBy ( $object );
		}
		
		if (! $object) {
			return;
		}
		
		$this->getEventManager()->trigger(__FUNCTION__, $this, array('entity' => $object));
		
		try{
    		$entityManager = $this->getEntityManager ();
    		$entityManager->remove ( $object );
    		$entityManager->flush ();
		}catch(\Exception $e){
			$this->getEventManager()->trigger(__FUNCTION__.'.error', $this, array('entity' => $object, 'exception' => $e));
		}
    		
		$this->getEventManager()->trigger(__FUNCTION__.'.post', $this, array('entity' => $object));
		
		return $this;
	}
	
	/**
	 * (non-PHPdoc)
	 * 
	 * @see \ZfcDB\Mapper\MapperInterface::deleteList()
	 */
	public function deleteList(array $objectList) {
		$entityManager = $this->getEntityManager ();
		
		$this->getEventManager()->trigger(__FUNCTION__, $this, array('entityCollection' => $objectList));
		
		if (count ( $objectList )) {
		    try{
    			foreach ( $objectList as $object ) {
    				$entityManager->remove ( $object );
    			}
    			$entityManager->flush ();
			}catch(\Exception $e){
				$this->getEventManager()->trigger(__FUNCTION__.'.error', $this, array('entity' => $object, 'entityCollection' => $objectList, 'exception' => $e));
			}
		}
		
		$this->getEventManager()->trigger(__FUNCTION__.'.post', $this, array('entityCollection' => $objectList));
		
		return $this;
	}
	
	/**
	 * (non-PHPdoc)
	 * 
	 * @see \ZfcBase\Mapper\AbstractDbMapper::select()
	 */
	public function fetch(array $criteria = null) {
		$repository = $this->getEntityManager ()->getRepository ( get_class ( $this->getEntityPrototype () ) );
		
		return $repository->findAll ();
	}
	
	/**
	 * (non-PHPdoc)
	 * 
	 * @see \ZfcDB\Mapper\MapperInterface::fetchById()
	 */
	public function fetchById($id) {
		$repository = $this->getEntityManager ()->getRepository ( get_class ( $this->getEntityPrototype () ) );
		
		return $repository->find ( $id );
	}
	
	/**
	 * (non-PHPdoc)
	 * 
	 * @see \ZfcDB\Mapper\MapperInterface::fetchBy()
	 */
	public function fetchBy(array $criteria, array $orderBy = null, $limit = null, $offset = null) {
		$repository = $this->getEntityManager ()->getRepository ( get_class ( $this->getEntityPrototype () ) );
		
		return $repository->findBy ( $criteria, $orderBy, $limit, $offset );
	}
	
	/**
	 * (non-PHPdoc)
	 * 
	 * @see \ZfcDB\Mapper\MapperInterface::fetchOneBy()
	 */
	public function fetchOneBy(array $criteria, array $orderBy = null) {
		$repository = $this->getEntityManager ()->getRepository ( get_class ( $this->getEntityPrototype () ) );
		
		$result = $repository->findBy ( $criteria, $orderBy, 1 );
		
		if (count ( $result )) {
			return $result [0];
		}
		return null;
	}
	
	/**
	 *
	 * @return \Doctrine\ORM\EntityManagerInterface
	 */
	public function getEntityManager() {
		return $this->entityManager;
	}
	
	/**
	 *
	 * @param unknown $entityManager        	
	 * @return \ZfcDoctrine\Mapper\MapperAbstract
	 */
	public function setEntityManager($entityManager) {
		$this->entityManager = $entityManager;
		return $this;
	}
}