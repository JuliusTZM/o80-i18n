<?php
namespace o80;

class I18NUnitTest extends \PHPUnit_Framework_TestCase {

    /**
     * @dataProvider availableLangsProvider
     */
    public function testShouldOrderAvailableLangs($getLang, $sessionLang, $acceptLangs, $defaultLang) {
        // given
        $_GET['lang'] = $getLang;
        $_SESSION['lang'] = $sessionLang;

        $i18n = $this->getMockBuilder('\\o80\\I18N')
            ->disableOriginalConstructor()
            ->setMethods(array('getHttpAcceptLanguages'))
            ->getMock();
        $i18n->setDefaultLang($defaultLang);
        $i18n->expects($this->once())
            ->method('getHttpAcceptLanguages')
            ->willReturn($acceptLangs);

        // when
        $langs = $i18n->getAvailableLangs();

        // then
        $expected = array($getLang, $sessionLang);
        $expected = array_merge($expected, $acceptLangs);
        $expected[] = $defaultLang;
        $this->assertEquals($expected, $langs);
    }

    public function availableLangsProvider() {
        return array(
            array('en', 'en', array('en'=>1), 'en'),
            array('fr', 'en_US', array('en'=>1), 'en'),
        );
    }

    /**
     * @dataProvider httpAcceptLAnguagesProvider
     */
    public function testGetHttpAcceptLanguages($httpAcceptLanguages, $expected) {
        // given
        $i18n = I18N::newInstance();
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $httpAcceptLanguages;

        // when
        $langs = $i18n->getHttpAcceptLanguages();

        // then
        $this->assertEquals($expected, $langs);
    }

    public function httpAcceptLAnguagesProvider() {
        return array(
            array('en', array('en')),
            array('en-US,en;q=0.8,fr-FR;q=0.5,fr;q=0.3', array('en_US', 'en', 'fr_FR', 'fr'))
        );
    }

    public function testLoadShouldCallDictProvider() {
        // given
        $i18n = I18N::newInstance();
        $providerMock = $this->getMock('\\o80\\DictProvider');

        $reflectionClass = new \ReflectionClass($i18n);
        $reflectionProperty = $reflectionClass->getProperty('dictProvider');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($i18n, $providerMock);

        // expects
        $providerMock->expects($this->once())->method('setLangsPath');
        $providerMock->expects($this->once())->method('load')->willReturn(array('Key'=>'Message'));

        // when
        $this->invoke($i18n, 'load', $i18n);

        // then
    }

    public function testDontLoadDictMoreThanOnce() {
        // given
        $i18n = $this->getMockBuilder('\\o80\\I18N')
            ->disableOriginalConstructor()
            ->setMethods(array('load'))
            ->getMock();

        // assert
        $i18n->expects($this->once())
            ->method('load')
            ->willReturn(array('a'=>'A', 'b'=>'B'));

        // when
        $a = $i18n->get('a');
        $b = $i18n->get('b');
        $missingKeyC = $i18n->get('c');

        // then
        $this->assertEquals('A', $a);
        $this->assertEquals('B', $b);
        $this->assertEquals('[missing key: c]', $missingKeyC);
    }

    /**
     * @expectedException \o80\CantLoadDictionaryException
     * @expectedExceptionMessage \o80\CantLoadDictionaryException::NO_MATCHING_FILES
     */
    public function testThrowExceptionWhenNoFileAreMatchingTheLanguages() {
        // given
        $i18n = I18N::newInstance();
        $providerMock = $this->getMock('\\o80\\DictProvider');

        $reflectionClass = new \ReflectionClass($i18n);
        $reflectionProperty = $reflectionClass->getProperty('dictProvider');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($i18n, $providerMock);

        // expects
        $providerMock->expects($this->once())->method('load')->willReturn(null);

        // when
        $this->invoke($i18n, 'load', $i18n);

        // then
    }

    private function invoke(&$object, $methodName) {
        $reflectionClass = new \ReflectionClass($object);
        $reflectionMethod = $reflectionClass->getMethod($methodName);
        $reflectionMethod->setAccessible(true);

        $params = array_slice(func_get_args(), 2); // get all the parameters after $methodName
        return $reflectionMethod->invokeArgs($object, $params);
    }

}
