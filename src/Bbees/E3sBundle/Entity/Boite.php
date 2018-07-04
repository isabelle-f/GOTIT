<?php

namespace Bbees\E3sBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Boite
 *
 * @ORM\Table(name="boite", uniqueConstraints={@ORM\UniqueConstraint(name="cu_boite_cle_primaire", columns={"code_boite"})}, indexes={@ORM\Index(name="IDX_7718EDEF9E7B0E1F", columns={"type_collection_voc_fk"}), @ORM\Index(name="IDX_7718EDEF41A72D48", columns={"code_collection_voc_fk"}), @ORM\Index(name="IDX_7718EDEF57552D30", columns={"type_boite_voc_fk"})})
 * @ORM\Entity
 */
class Boite
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="boite_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="code_boite", type="string", length=255, nullable=false)
     */
    private $codeBoite;

    /**
     * @var string
     *
     * @ORM\Column(name="libelle_boite", type="string", length=1024, nullable=true)
     */
    private $libelleBoite;

    /**
     * @var string
     *
     * @ORM\Column(name="libelle_collection", type="string", length=1024, nullable=true)
     */
    private $libelleCollection;

    /**
     * @var string
     *
     * @ORM\Column(name="commentaire_boite", type="text", nullable=true)
     */
    private $commentaireBoite;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_cre", type="datetime", nullable=true)
     */
    private $dateCre;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_maj", type="datetime", nullable=true)
     */
    private $dateMaj;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_cre", type="bigint", nullable=true)
     */
    private $userCre;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_maj", type="bigint", nullable=true)
     */
    private $userMaj;

    /**
     * @var \Voc
     *
     * @ORM\ManyToOne(targetEntity="Voc")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="type_collection_voc_fk", referencedColumnName="id")
     * })
     */
    private $typeCollectionVocFk;

    /**
     * @var \Voc
     *
     * @ORM\ManyToOne(targetEntity="Voc")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="code_collection_voc_fk", referencedColumnName="id")
     * })
     */
    private $codeCollectionVocFk;

    /**
     * @var \Voc
     *
     * @ORM\ManyToOne(targetEntity="Voc")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="type_boite_voc_fk", referencedColumnName="id")
     * })
     */
    private $typeBoiteVocFk;



    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set codeBoite
     *
     * @param string $codeBoite
     *
     * @return Boite
     */
    public function setCodeBoite($codeBoite)
    {
        $this->codeBoite = $codeBoite;

        return $this;
    }

    /**
     * Get codeBoite
     *
     * @return string
     */
    public function getCodeBoite()
    {
        return $this->codeBoite;
    }

    /**
     * Set libelleBoite
     *
     * @param string $libelleBoite
     *
     * @return Boite
     */
    public function setLibelleBoite($libelleBoite)
    {
        $this->libelleBoite = $libelleBoite;

        return $this;
    }

    /**
     * Get libelleBoite
     *
     * @return string
     */
    public function getLibelleBoite()
    {
        return $this->libelleBoite;
    }

    /**
     * Set libelleCollection
     *
     * @param string $libelleCollection
     *
     * @return Boite
     */
    public function setLibelleCollection($libelleCollection)
    {
        $this->libelleCollection = $libelleCollection;

        return $this;
    }

    /**
     * Get libelleCollection
     *
     * @return string
     */
    public function getLibelleCollection()
    {
        return $this->libelleCollection;
    }

    /**
     * Set commentaireBoite
     *
     * @param string $commentaireBoite
     *
     * @return Boite
     */
    public function setCommentaireBoite($commentaireBoite)
    {
        $this->commentaireBoite = $commentaireBoite;

        return $this;
    }

    /**
     * Get commentaireBoite
     *
     * @return string
     */
    public function getCommentaireBoite()
    {
        return $this->commentaireBoite;
    }

    /**
     * Set dateCre
     *
     * @param \DateTime $dateCre
     *
     * @return Boite
     */
    public function setDateCre($dateCre)
    {
        $this->dateCre = $dateCre;

        return $this;
    }

    /**
     * Get dateCre
     *
     * @return \DateTime
     */
    public function getDateCre()
    {
        return $this->dateCre;
    }

    /**
     * Set dateMaj
     *
     * @param \DateTime $dateMaj
     *
     * @return Boite
     */
    public function setDateMaj($dateMaj)
    {
        $this->dateMaj = $dateMaj;

        return $this;
    }

    /**
     * Get dateMaj
     *
     * @return \DateTime
     */
    public function getDateMaj()
    {
        return $this->dateMaj;
    }

    /**
     * Set userCre
     *
     * @param integer $userCre
     *
     * @return Boite
     */
    public function setUserCre($userCre)
    {
        $this->userCre = $userCre;

        return $this;
    }

    /**
     * Get userCre
     *
     * @return integer
     */
    public function getUserCre()
    {
        return $this->userCre;
    }

    /**
     * Set userMaj
     *
     * @param integer $userMaj
     *
     * @return Boite
     */
    public function setUserMaj($userMaj)
    {
        $this->userMaj = $userMaj;

        return $this;
    }

    /**
     * Get userMaj
     *
     * @return integer
     */
    public function getUserMaj()
    {
        return $this->userMaj;
    }

    /**
     * Set typeCollectionVocFk
     *
     * @param \Bbees\E3sBundle\Entity\Voc $typeCollectionVocFk
     *
     * @return Boite
     */
    public function setTypeCollectionVocFk(\Bbees\E3sBundle\Entity\Voc $typeCollectionVocFk = null)
    {
        $this->typeCollectionVocFk = $typeCollectionVocFk;

        return $this;
    }

    /**
     * Get typeCollectionVocFk
     *
     * @return \Bbees\E3sBundle\Entity\Voc
     */
    public function getTypeCollectionVocFk()
    {
        return $this->typeCollectionVocFk;
    }

    /**
     * Set codeCollectionVocFk
     *
     * @param \Bbees\E3sBundle\Entity\Voc $codeCollectionVocFk
     *
     * @return Boite
     */
    public function setCodeCollectionVocFk(\Bbees\E3sBundle\Entity\Voc $codeCollectionVocFk = null)
    {
        $this->codeCollectionVocFk = $codeCollectionVocFk;

        return $this;
    }

    /**
     * Get codeCollectionVocFk
     *
     * @return \Bbees\E3sBundle\Entity\Voc
     */
    public function getCodeCollectionVocFk()
    {
        return $this->codeCollectionVocFk;
    }

    /**
     * Set typeBoiteVocFk
     *
     * @param \Bbees\E3sBundle\Entity\Voc $typeBoiteVocFk
     *
     * @return Boite
     */
    public function setTypeBoiteVocFk(\Bbees\E3sBundle\Entity\Voc $typeBoiteVocFk = null)
    {
        $this->typeBoiteVocFk = $typeBoiteVocFk;

        return $this;
    }

    /**
     * Get typeBoiteVocFk
     *
     * @return \Bbees\E3sBundle\Entity\Voc
     */
    public function getTypeBoiteVocFk()
    {
        return $this->typeBoiteVocFk;
    }
}