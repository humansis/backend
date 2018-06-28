<?php

namespace ReportingBundle\Model;

interface IndicatorInterface
{

    /**
     * get id
    */
    public function getId();

   /**
     * get code
    */
    public function setReference($reference);

    /**
     * get reference
    */
    public function getReference();

    /**
     * set code
    */
    public function setCode($code);

    /**
     * get code
    */
    public function getCode();

    /**
     * set filtres
    */
    public function setFiltres($filtres = null);

    /**
     * get filtres
    */
    public function getFiltres();

    /**
     * set grpahique
    */
    public function setGraphique($graphique = null);

    /**
     * get graphique
    */
    public function getGraphique();


}