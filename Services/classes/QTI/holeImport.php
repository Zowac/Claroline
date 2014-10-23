<?php

/**
 * To import a question with holes in QTI
 *
 */

namespace UJM\ExoBundle\Services\classes\QTI;

use UJM\ExoBundle\Entity\Hole;
use UJM\ExoBundle\Entity\InteractionHole;
use UJM\ExoBundle\Entity\WordResponse;

class holeImport extends qtiImport
{
    protected $interactionHole;
    protected $qtiTextWithHoles;
    protected $textHtml;

    /**
     * Implements the abstract method
     *
     * @access public
     * @param qtiRepository $qtiRepos
     *
     */
    public function import(qtiRepository $qtiRepos, \DOMDocument $document)
    {
        $this->qtiRepos = $qtiRepos;
        $this->document = $document;
        $this->getQTICategory();
        $this->initAssessmentItem();

        $this->createQuestion();

        $this->createInteraction();
        $this->interaction->setType('InteractionHole');
        $this->doctrine->getManager()->persist($this->interaction);

        $this->createInteractionHole();

        $this->doctrine->getManager()->flush();
    }

    /**
     * Create the InteractionHole object
     *
     * @access protected
     *
     */
    protected function createInteractionHole()
    {
        $this->interactionHole = new InteractionHole();
        $this->interactionHole->setInteraction($this->interaction);

        $this->getQtiTextWithHoles();
        $this->getHtml();
        $this->getHtmlWithoutValue();

        $this->doctrine->getManager()->persist($this->interactionHole);
    }

    /**
     * Get property html
     *
     * @access protected
     *
     */
    protected function getHtml()
    {
        $this->textHtml = $this->qtiTextWithHoles;
        $newId = 1;
        $regex = '(<textEntryInteraction.*?>|<inlineChoiceInteraction.*?</inlineChoiceInteraction>)';
        preg_match_all($regex, $this->qtiTextWithHoles, $matches);
        foreach ($matches[0] as $matche) {
            $tabMatche = explode('"', $matche);
            $responseIdentifier = $tabMatche[1];
            $correctResponse    = $this->getCorrectResponse($responseIdentifier);
            if (substr($matche, 1, 20) == 'textEntryInteraction') {
                $expectedLength = $tabMatche[3];
                $text = str_replace('textEntryInteraction', 'input', $matche);
                $text = str_replace('responseIdentifier="'.$responseIdentifier.'"', 'id="'.$newId.'" class="blank" autocomplete="off" name="blank_'.$newId.'"', $text);
                $text = str_replace('expectedLength="'.$expectedLength.'"', 'size="'.$expectedLength.'" type="text" value="'.$correctResponse.'"', $text);
                $this->createHole($expectedLength, $responseIdentifier, false, $newId);
            } else {
               $text = str_replace('inlineChoiceInteraction', 'select', $matche);
               $text = str_replace('responseIdentifier="'.$responseIdentifier.'"', 'id="'.$newId.'" class="blank" name="blank_'.$newId.'"', $text);
               $text = str_replace('inlineChoice', 'option', $text);
               $regexOpt = '(<option identifier=.*?>)';
               preg_match_all($regexOpt, $text, $matchesOpt);
               foreach ($matchesOpt[0] as $matcheOpt) {
                   $tabMatcheOpt = explode('"', $matcheOpt);
                   $holeID       = $tabMatcheOpt[1];
                   $opt = preg_replace('(\s*identifier="'.$holeID.'")', '', $matcheOpt);
                   $text = str_replace($matcheOpt, $opt, $text);
               }
               $this->createHole(15, $responseIdentifier, true, $newId);
            }
            $newId++;
            $this->textHtml = str_replace($matche, $text, $this->textHtml);
        }
        $this->interactionHole->setHtml($this->textHtml);
    }

    /**
     * Get correctResponse
     *
     * @access protected
     *
     * @param String $identifier identifier of hole
     *
     */
    protected function getCorrectResponse($identifier)
    {
        $correctResponse = '';
        foreach($this->assessmentItem->getElementsByTagName("responseDeclaration") as $rp) {
            if ($rp->getAttribute("identifier") == $identifier) {
                $correctResponse = $rp->getElementsByTagName("correctResponse")
                                      ->item(0)->getElementsByTagName("value")
                                      ->item(0)->nodeValue;
            }
        }

        return $correctResponse;
    }

    /**
     * Get property htmlWithoutValue
     *
     * @access protected
     *
     */
    protected function getHtmlWithoutValue()
    {
        $htmlWithoutValue = $this->textHtml;
        $regex = '(<input.*?class="blank".*?>)';
        preg_match_all($regex, $htmlWithoutValue, $matches);
        foreach ($matches[0] as $matche) {
            if (substr($matche, 1, 5) == 'input') {
                $tabMatche = explode('"', $matche);
                $value = $tabMatche[13];
                $inputWithoutValue = str_replace('value="'.$value.'"', 'value=""', $matche);
                $htmlWithoutValue = str_replace($matche, $inputWithoutValue, $htmlWithoutValue);
            }
        }
        $this->interactionHole->sethtmlWithoutValue($htmlWithoutValue);
    }

    /**
     * Create hole
     *
     * @access protected
     *
     * @param Intger $size hole's size for the input
     * @param String $qtiId id of hole in the qti file
     * @param boolean $selector text or list
     * @param Integer $position position of hole in the text
     *
     */
    protected function createHole($size, $qtiId, $selector, $position)
    {
        $hole = new Hole();
        $hole->setSize($size);
        $hole->setSelector($selector);
        $hole->setPosition($position);
        $hole->setInteractionHole($this->interactionHole);

        $this->doctrine->getManager()->persist($hole);

        $this->createWordResponse($qtiId, $hole);
    }

    /**
     * Create wordResponse
     *
     * @access protected
     *
     * @param String $qtiId id of hole in the qti file
     * @param UJM\ExoBundle\Entity\Hole $hole
     *
     */
    protected function createWordResponse($qtiId, $hole)
    {
        foreach($this->assessmentItem->getElementsByTagName("responseDeclaration") as $rp) {
            if ($rp->getAttribute("identifier") == $qtiId) {
                $mapping = $rp->getElementsByTagName("mapping")->item(0);
                foreach ($mapping->getElementsByTagName("mapEntry") as $mapEntry) {
                    $keyWord = new WordResponse();
                    if ($hole->getSelector() === false) {
                        $keyWord->setResponse($mapEntry->getAttribute('mapKey'));
                        $keyWord->setScore($mapEntry->getAttribute('mappedValue'));
                        $keyWord->setHole($hole);
                        $this->doctrine->getManager()->persist($keyWord);
                    }
                }
            }
        }
    }

    /**
     * Get qtiTextWithHoles
     *
     * @access protected
     *
     */
    protected function getQtiTextWithHoles()
    {
        $ib = $this->assessmentItem->getElementsByTagName("itemBody")->item(0);
        $text = $this->document->saveXML($ib);
        $text = str_replace('<itemBody>', '', $text);
        $text = str_replace('</itemBody>', '', $text);
        $text = trim($text);
        $this->qtiTextWithHoles = html_entity_decode($text);
    }

    /**
     * Implements the abstract method
     *
     * @access protected
     *
     */
    protected function getPrompt()
    {
        $prompt = '';
        $ib = $this->assessmentItem->getElementsByTagName("itemBody")->item(0);
        if ($ib->getElementsByTagName("prompt")->item(0)) {
            $prompt = $ib->getElementsByTagName("prompt")->item(0)->nodeValue;
            $ib->removeChild($ib->getElementsByTagName("prompt")->item(0));
        }

        return $prompt;
    }
}
