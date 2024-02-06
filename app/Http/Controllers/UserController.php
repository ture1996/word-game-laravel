<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use LanguageDetection\Language;

class UserController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

    //forbids other users to manage their account and see their personal data

    private function checkUser($id)
    {
        if ($id != Auth::user()->id) {
            throw new Exception("Not allowed!");
        };
    }

    //shows personal_best and nick_name of all players

    public function index()
    {
        return User::select('nick_name', 'personal_best')->orderBy('personal_best', 'DESC')->get();
    }

    //shows personal_info of logged player

    public function show($id)
    {
        $this->checkUser($id);

        return User::findOrFail($id);
    }

    //updates nick_name, game and personal_best attributes

    public function update($id, UserRequest $request)
    {

        $this->checkUser($id);

        //checks if empty request is sent and if is throws exception

        if (empty($request->all())) {
            throw new Exception("You must enter a word!");
        }

        $game = Auth::user()->game;
        $newGame = [
            'is_ongoing' => false,
            'score' => 0,
            'words' => [],
            'attempts_remaining' => 3
        ];

        //updates game attribute when user wants to start or finish game

        if (isset($request->is_ongoing)) {
            $game['is_ongoing'] = true;
            return User::findOrFail($id)->update(['game' => $request->is_ongoing ? $game : $newGame]);
        }

        //prevents user to play game if game status isn't ongoing

        if (!$game['is_ongoing'] && isset($request->word)) {
            throw new Exception("You need to start game!");
        }

        //updates nick_name attribute if game isn't ongoing

        if (!$game['is_ongoing'] && isset($request->nick_name)) {
            return User::findOrFail($id)->update(['nick_name' => $request->nick_name]);
        }

        //prevents user to change nick_name if game is ongoing

        if ($game['is_ongoing'] && isset($request->nick_name)) {
            throw new Exception("Can't change nick name while in game!");
        }

        //used to check users word, eliminate case sensitive
        //and remove whitespaces

        $wordToCheck = str_replace(' ', '', strtoupper($request->word));

        //checks probability that word that user sent is on English language

        $languageDetector = new Language();
        $language = $languageDetector->detect($wordToCheck)->close();

        //checks if word is already typed in this game and
        //if word is on English language with probability less then 40%
        //in that case attempts_remaining are subtracted by 1

        if (in_array($wordToCheck, Auth::user()->game['words']) || (in_array('en', array_keys($language)) && $language['en'] < 0.4) || preg_match('~[0-9]+~', $wordToCheck)) {
            $game['attempts_remaining']--;

            //if user don't have more attempts game will stop
            //and if his score in that game is higher then personal_best
            //personal_best attribute will be updated

            if ($game['attempts_remaining'] == 0) {
                if ($game['score'] > Auth::user()->personal_best) {
                    $newBestScore = $game['score'];
                    return User::findOrFail($id)->update(['personal_best' => $newBestScore, 'game' => $newGame]);
                };
                $game = $newGame;
            }
        } else {

            //getting 1 point for every unique letter in word

            $addToScore = strlen(count_chars($wordToCheck, 3));

            //checking if word is palindrome

            function isPalindrome($word)
            {
                return (strrev($word) == $word);
            };

            //checking if word is almost palindrome

            function isAlmostPalindrome($word)
            {
                for ($i = 0; $i < strlen($word); $i++) {
                    $wordWithoutOneChar = substr_replace($word, '', $i, 1);
                    if (isPalindrome($wordWithoutOneChar)) {
                        return true;
                    };
                }
                return false;
            }

            //if word is palindrome user will get extra 3 points
            //or if word length is higher then 2 
            //and if it is almost palindrome user will get extra 2 points

            if (isPalindrome($wordToCheck)) {
                $addToScore += 3;
            } else if (strlen($wordToCheck) > 2 && isAlmostPalindrome($wordToCheck)) {
                $addToScore += 2;
            }

            $game['score'] += $addToScore;
            array_push($game['words'], $wordToCheck);
        };

        return User::findOrFail($id)->update(['game' => $game]);
    }

    //only user can delete it's account

    public function destroy($id)
    {
        $this->checkUser($id);

        return User::destroy($id);
    }
}
