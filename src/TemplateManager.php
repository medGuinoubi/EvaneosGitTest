<?php

class TemplateManager
{
    public function getTemplateComputed(Template $tpl, array $data)
    {
        if (!$tpl) {
            throw new \RuntimeException('no tpl given');
        }
        //
        $replaced = clone($tpl);
        $replaced->subject = $this->computeText($replaced->subject, $data);
        $replaced->content = $this->computeText($replaced->content, $data);

        return $replaced;
    }

    //this function replaces all the placeholders present in the template text by the user's data such as the name, the destination, etc
    private function computeText($text, array $data)
    {
        $APPLICATION_CONTEXT = ApplicationContext::getInstance();
        //checking if the quote is set
        if (isset($data['quote']) and $data['quote'] instanceof Quote){
            $quote=$data['quote'];
        }else{
            $quote=null;
        }


        if ($quote)
        {
            //get quote
            $_quoteFromRepository = QuoteRepository::getInstance()->getById($quote->id);
            //get subject
            $usefulObject = SiteRepository::getInstance()->getById($quote->siteId);
            //get destination
            $destinationOfQuote = DestinationRepository::getInstance()->getById($quote->destinationId);
            //check if the destination needle exists , replace it by the user's destination
            if(strpos($text, '[quote:destination_link]') !== false){
                $destination = DestinationRepository::getInstance()->getById($quote->destinationId);
            }


            $containsSummaryHtml = strpos($text, '[quote:summary_html]');
            $containsSummary     = strpos($text, '[quote:summary]');

            //if the template contains summary placeholder, replace it by the user's summary (or the HTML summary if it exists too)
            if ($containsSummaryHtml !== false || $containsSummary !== false) {
                if ($containsSummaryHtml !== false) {
                    $text = str_replace(
                        '[quote:summary_html]',
                        Quote::renderHtml($_quoteFromRepository),
                        $text
                    );
                }
                if ($containsSummary !== false) {
                    $text = str_replace(
                        '[quote:summary]',
                        Quote::renderText($_quoteFromRepository),
                        $text
                    );
                }
            }

            (strpos($text, '[quote:destination_name]') !== false) and $text = str_replace('[quote:destination_name]',$destinationOfQuote->countryName,$text);
        }

        if (isset($destination))
        {
            $text = str_replace('[quote:destination_link]', $usefulObject->url . '/' . $destination->countryName . '/quote/' . $_quoteFromRepository->id, $text);
        }
        else
        {
            $text = str_replace('[quote:destination_link]', '', $text);
        }

        /*
         * USER
         * [user:*]
         */
        if((isset($data['user'])  and ($data['user']  instanceof User))){
            $_user  =  $data['user'];
        } else{
            $_user = $APPLICATION_CONTEXT->getCurrentUser();
        }

        if($_user) {
            (strpos($text, '[user:first_name]') !== false) and $text = str_replace('[user:first_name]'       , ucfirst(mb_strtolower($_user->firstname)), $text);
        }

        return $text;
    }
}
