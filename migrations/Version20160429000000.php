<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\LeadBundle\Entity\DoNotContact;

/**
 * Trackables
 */
class Version20160429000000 extends AbstractMauticMigration
{
    private $redirectIdx;
    private $redirectFk;

    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->hasTable($this->prefix.'channel_url_trackables')) {

            throw new SkipMigrationException('Schema includes this migration');
        }

        $this->redirectIdx = $this->generatePropertyName($this->prefix . 'channel_url_trackables', 'idx', array('redirect_id'));
        $this->redirectFk  = $this->generatePropertyName($this->prefix . 'channel_url_trackables', 'fk', array('redirect_id'));
    }

    /**
     * @param Schema $schema
     */
    public function mysqlUp(Schema $schema)
    {

        $sql = <<<SQL
CREATE TABLE {$this->prefix}channel_url_trackables (
  redirect_id INT NOT NULL, 
  channel_id INT NOT NULL, 
  channel VARCHAR(255) NOT NULL, 
  hits INT NOT NULL, 
  unique_hits INT NOT NULL, 
  INDEX {$this->redirectIdx} (redirect_id),
  INDEX {$this->prefix}channel_url_trackable_search (channel, channel_id),
  PRIMARY KEY(redirect_id, channel_id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
SQL;
        $this->addSql($sql);

        $this->addSql("ALTER TABLE {$this->prefix}channel_url_trackables ADD CONSTRAINT {$this->redirectFk} FOREIGN KEY (redirect_id) REFERENCES {$this->prefix}page_redirects (id) ON DELETE CASCADE");

        $this->addSql("CREATE INDEX {$this->prefix}page_redirect_url_search ON {$this->prefix}page_redirects (url)");
    }

    /**
     * @param Schema $schema
     */
    public function postgresqlUp(Schema $schema)
    {
        $sql = <<<SQL
CREATE TABLE {$this->prefix}channel_url_trackables (
  redirect_id INT NOT NULL, 
  channel_id INT NOT NULL, 
  channel VARCHAR(255) NOT NULL, 
  hits INT NOT NULL, 
  unique_hits INT NOT NULL, PRIMARY KEY(redirect_id, channel_id)
)
SQL;
        $this->addSql($sql);

        $this->addSql("CREATE INDEX {$this->redirectIdx} ON {$this->prefix}channel_url_trackables (redirect_id)");
        $this->addSql("CREATE INDEX {$this->prefix} ON {$this->prefix}channel_url_trackables (channel, channel_id)");
        $this->addSql("ALTER TABLE {$this->prefix}channel_url_trackables ADD CONSTRAINT {$this->redirectFk} FOREIGN KEY (redirect_id) REFERENCES {$this->prefix}page_redirects (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE");

        $this->addSql("CREATE INDEX {$this->prefix}page_redirect_url_search ON {$this->prefix}page_redirects (url)");
    }

    /**
     * Migrate email redirects to the trackable table
     *
     * @param Schema $schema
     */
    public function postUp(Schema $schema)
    {
        $logger = $this->factory->getLogger();
        $qb = $this->connection->createQueryBuilder();

        $qb->select('r.id, r.email_id, r.hits, r.unique_hits')
            ->from($this->prefix.'page_redirects', 'r')
            ->where(
                $qb->expr()->isNotNull('r.email_id')
            )
            ->setMaxResults(500);

        $start = 0;
        while ($results = $qb->execute()->fetchAll()) {
            // Start a transaction
            $this->connection->beginTransaction();

            foreach ($results as $row) {
                $insert = array(
                    'redirect_id' => $row['id'],
                    'channel'     => 'email',
                    'channel_id'  => $row['email_id'],
                    'hits'        => $row['hits'],
                    'unique_hits' => $row['unique_hits']
                );

                $this->connection->insert($this->prefix.'channel_url_trackables', $insert);

                unset($insert);
            }

            try {
                $this->connection->commit();
            } catch (\Exception $e) {
                $this->connection->rollBack();

                $logger->addError($e->getMessage(), array('exception' => $e));
            }

            // Increase the start
            $start += 500;
            $qb->setFirstResult($start);
        }
    }
}