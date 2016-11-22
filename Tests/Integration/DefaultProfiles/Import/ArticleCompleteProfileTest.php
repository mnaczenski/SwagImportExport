<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Integration\DefaultProfiles\Import;

use SwagImportExport\Tests\Helper\CommandTestCaseTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;
use SwagImportExport\Tests\Integration\DefaultProfiles\DefaultProfileImportTestCaseTrait;

class ArticleCompleteProfileTest extends \PHPUnit_Framework_TestCase
{
    use DatabaseTestCaseTrait;
    use CommandTestCaseTrait;
    use DefaultProfileImportTestCaseTrait;

    protected function setUp()
    {
        $csvFile = __DIR__ . '/_fixtures/article_complete.csv';
        $fixtureImagePath = 'file://' . realpath(__DIR__) . '/../../../Helper/ImportFiles/sw-icon_blue128.png';
        $csvContentWithExternalImagePath = str_replace('[placeholder_for_fixture_image]', $fixtureImagePath, file_get_contents($csvFile));
        file_put_contents($csvFile, $csvContentWithExternalImagePath);
    }

    protected function tearDown()
    {
        $csvFile = __DIR__ . '/_fixtures/article_complete.csv';
        $fixtureImagePath = 'file://' . realpath(__DIR__) . '/../../../Helper/ImportFiles/sw-icon_blue128.png';
        $csvContentWithPlaceholder = str_replace($fixtureImagePath, '[placeholder_for_fixture_image]', file_get_contents($csvFile));
        file_put_contents($csvFile, $csvContentWithPlaceholder);
    }

    public function test_import_should_create_article_with_variants()
    {
        $filePath = __DIR__ . '/_fixtures/article_complete.csv';
        $this->runCommand("sw:import:import -p default_articles_complete {$filePath}");

        $importedArticle = $this->executeQuery('SELECT * FROM s_articles WHERE name="Article with Variants"');
        $this->assertEquals('Article with Variants', $importedArticle[0]['name']);
        $this->assertEquals(1, $importedArticle[0]['active']);

        $importedVariants = $this->executeQuery("SELECT * FROM s_articles_details WHERE articleID={$importedArticle[0]['id']} ORDER BY ordernumber");
        $this->assertCount(6, $importedVariants, 'Import did not import expected 6 variants');
        $this->assertEquals(999, $importedVariants[1]['instock']);
        $this->assertEquals('with different instock', $importedVariants[1]['additionaltext']);
    }

    public function test_import_should_create_variant_with_different_prices_for_customer_groups()
    {
        $filePath = __DIR__ . '/_fixtures/article_complete.csv';
        $this->runCommand("sw:import:import -p default_articles_complete {$filePath}");

        $importedVariantWithDifferentPrice = $this->executeQuery("SELECT * FROM s_articles_details WHERE ordernumber='test-10001.2'");

        $importedVariantPrice = $this->executeQuery("SELECT * FROm s_articles_prices WHERE articledetailsID={$importedVariantWithDifferentPrice[0]['id']} ORDER BY pricegroup");
        $this->assertEquals(839.49579831933, $importedVariantPrice[0]['price'], 'Could not import gross price for customer group EK as net price.');
        $this->assertEquals(550, $importedVariantPrice[1]['price'], 'Could not import price for customer group H');
    }

    public function test_import_should_create_article_with_different_prices_for_customer_groups()
    {
        $filePath = __DIR__ . '/_fixtures/article_complete.csv';
        $this->runCommand("sw:import:import -p default_articles_complete {$filePath}");

        $importedMainVariant = $this->executeQuery("SELECT * FROM s_articles_details WHERE ordernumber='test-10001.1'");

        $importedArticlePrice = $this->executeQuery("SELECT * FROM s_articles_prices WHERE articledetailsID={$importedMainVariant[0]['id']} ORDER BY pricegroup");
        $this->assertEquals(84.033613445378, $importedArticlePrice[0]['price'], 'Could not import gross price for customer group EK as net price.');
        $this->assertEquals(150, $importedArticlePrice[1]['price'], 'Could not import price for customer group H');
    }

    public function test_import_should_import_article_with_attributes()
    {
        $filePath = __DIR__ . '/_fixtures/article_complete.csv';
        $this->runCommand("sw:import:import -p default_articles_complete {$filePath}");

        $importedArticle = $this->executeQuery('SELECT * FROM s_articles WHERE name="Article with Variants"');
        $importedAttributes = $this->executeQuery("SELECT * FROM s_articles_attributes WHERE articleID={$importedArticle[0]['id']}");
        $this->assertEquals('attribute 1', $importedAttributes[0]['attr1']);
        $this->assertEquals('attribute 2', $importedAttributes[0]['attr2']);
        $this->assertEquals('comment', $importedAttributes[0]['attr3']);
    }

    public function test_import_should_import_variant_with_attributes()
    {
        $filePath = __DIR__ . '/_fixtures/article_complete.csv';
        $this->runCommand("sw:import:import -p default_articles_complete {$filePath}");

        $importedVariant = $this->executeQuery('SELECT * FROM s_articles_details WHERE ordernumber="test-10001.1"');
        $importedAttributes = $this->executeQuery("SELECT * FROM s_articles_attributes WHERE articledetailsID={$importedVariant[0]['id']}");
        $this->assertEquals('attribute1', $importedAttributes[0]['attr1']);
        $this->assertEquals('attribute2', $importedAttributes[0]['attr2']);
        $this->assertEquals('comment', $importedAttributes[0]['attr3']);
    }

    public function test_import_should_import_translations()
    {
        $filePath = __DIR__ . '/_fixtures/article_complete.csv';
        $this->runCommand("sw:import:import -p default_articles_complete {$filePath}");

        $importedVariant = $this->executeQuery('SELECT * FROM s_articles_details WHERE ordernumber="test-10001.1"');
        $result = $this->executeQuery("SELECT * FROM s_core_translations WHERE objecttype='article' AND objectkey='{$importedVariant[0]['articleID']}'");
        $importedTranslation = unserialize($result[0]['objectdata']);

        $this->assertEquals('Translated Name', $importedTranslation['txtArtikel']);
        $this->assertEquals('short description translation', $importedTranslation['txtshortdescription']);
        $this->assertEquals('meta title description', $importedTranslation['metaTitle']);
    }

    public function test_import_should_create_similar_associations()
    {
        $filePath = __DIR__ . '/_fixtures/article_complete.csv';
        $this->runCommand("sw:import:import -p default_articles_complete {$filePath}");

        $importedArticle = $this->executeQuery('SELECT * FROM s_articles WHERE name="Article with Variants"');
        $similarRelation = $this->executeQuery("SELECT * FROM s_articles_similar WHERE articleID={$importedArticle[0]['id']}");
        $createdSimilarArticle = $this->executeQuery("SELECT * FROM s_articles WHERE id={$similarRelation[0]['relatedarticle']}");

        $this->assertEquals('Similar article', $createdSimilarArticle[0]['name']);
    }

    public function test_import_should_create_accessory_associations()
    {
        $filePath = __DIR__ . '/_fixtures/article_complete.csv';
        $this->runCommand("sw:import:import -p default_articles_complete {$filePath}");

        $importedArticle = $this->executeQuery('SELECT * FROM s_articles WHERE name="Article with Variants"');
        $similarRelation = $this->executeQuery("SELECT * FROM s_articles_relationships WHERE articleID={$importedArticle[0]['id']}");
        $createdSimilarArticle = $this->executeQuery("SELECT * FROM s_articles WHERE id={$similarRelation[0]['relatedarticle']}");

        $this->assertEquals('Accessory article', $createdSimilarArticle[0]['name']);
    }

    public function test_import_should_create_media_from_external_ressource()
    {
        $filePath = __DIR__ . '/_fixtures/article_complete.csv';
        $this->runCommand("sw:import:import -p default_articles_complete {$filePath}");

        $importedArticle = $this->executeQuery('SELECT * FROM s_articles WHERE name="Article with Variants"');
        $mediaRelation = $this->executeQuery("SELECT * FROM s_articles_img WHERE articleID='{$importedArticle[0]['id']}'");
        $mediaFromExternalResource = $this->executeQuery("SELECT * FROM s_media WHERE id='{$mediaRelation[0]['media_id']}'");

        $this->assertStringStartsWith('media/image/sw-icon_blue', $mediaFromExternalResource[0]['path']);
    }
}
