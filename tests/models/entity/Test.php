<?php

/**
 * Test.php
 *
 * @author Zhang Yi <loeyae@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License
 * @version 2020/5/2 16:07
 */

namespace loeye\models\entity;



use loeye\database\Entity;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Class Test
 *
 * @ORM\Table(name="test")
 * @ORM\Entity(repositoryClass="\loeye\models\repository\TestRepository")
 */
class Test extends Entity {

    /**
     * @var
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, options={"comment"="ID"}, unique=false)
     * @ORM\Id
     */
    private $id;

    /**
     * @var
     *
     * @ORM\Column(name="name", type="string", length=32, precision=0, scale=0, nullable=false, options={"comment"="名称"}, unique=false)
     *
     */
    private $name;

    /**
     * @var
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="create_time", type="datetime", precision=0, scale=0, nullable=false, options={"default"="CURRENT_TIMESTAMP","comment"="创建时间"}, unique=false)
     *
     */
    private $createTime;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getCreateTime()
    {
        return $this->createTime;
    }

    /**
     * @param mixed $createTime
     */
    public function setCreateTime($createTime): void
    {
        $this->createTime = $createTime;
    }

}
