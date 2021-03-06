<?php
/**
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://phing.info>.
 */

/**
 * Uses regular expressions to perform filename transformations.
 *
 * @author Andreas Aderhold <andi@binarycloud.com>
 * @author Hans Lellelid <hans@velum.net>
 *
 * @package phing.mappers
 */
class RegexpMapper implements FileNameMapper
{
    /**
     * @var string $to
     */
    private $to;

    /**
     * The Regexp engine.
     *
     * @var Regexp $reg
     */
    private $reg;

    private $handleDirSep = false;
    private $caseSensitive = true;

    /**
     * Instantiage regexp matcher here.
     */
    public function __construct()
    {
        $this->reg = new Regexp();
        $this->reg->setIgnoreCase(!$this->caseSensitive);
    }

    /**
     * Attribute specifying whether to ignore the difference
     * between / and \ (the two common directory characters).
     *
     * @param boolean $handleDirSep a boolean, default is false.
     */
    public function setHandleDirSep($handleDirSep)
    {
        $this->handleDirSep = $handleDirSep;
    }

    /**
     * Attribute specifying whether to ignore the difference
     * between / and \ (the two common directory characters).
     */
    public function getHandleDirSep()
    {
        return $this->handleDirSep;
    }

    /**
     * Attribute specifying whether to ignore the case difference
     * in the names.
     *
     * @param boolean $caseSensitive a boolean, default is false.
     */
    public function setCaseSensitive($caseSensitive)
    {
        $this->caseSensitive = $caseSensitive;
    }

    /**
     * Sets the &quot;from&quot; pattern. Required.
     * {@inheritdoc}
     *
     * @param string $from
     *
     * @return void
     */
    public function setFrom($from)
    {
        if ($from === null) {
            throw new BuildException("this mapper requires a 'from' attribute");
        }

        $this->reg->setPattern($from);
    }

    /**
     * Sets the &quot;to&quot; pattern. Required.
     *
     * {@inheritdoc}
     *
     * @param string $to
     *
     * @return void
     *
     * @intern [HL] I'm changing the way this works for now to just use string
     *              <code>$this->to = StringHelper::toCharArray($to);</code>
     */
    public function setTo($to)
    {
        if ($to === null) {
            throw new BuildException("this mapper requires a 'to' attribute");
        }

        $this->to = $to;
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $sourceFileName
     *
     * @return array|null
     */
    public function main($sourceFileName)
    {
        if ($this->handleDirSep) {
            if (strpos('\\', $sourceFileName) !== false) {
                $sourceFileName = str_replace('\\', '/', $sourceFileName);
            }
        }
        if ($this->reg === null || $this->to === null || !$this->reg->matches((string) $sourceFileName)) {
            return null;
        }

        return [$this->replaceReferences($sourceFileName)];
    }

    /**
     * Replace all backreferences in the to pattern with the matched groups.
     * groups of the source.
     *
     * @param string $source The source filename.
     *
     * @return array|null|string
     *
     * FIXME Can't we just use engine->replace() to handle this?  the Preg engine will automatically convert \1 references to $1
     *
     * @intern the expression has already been processed (when ->matches() was run in Main())
     *         so no need to pass $source again to the engine.
     *         Replaces \1 with value of reg->getGroup(1) and return the modified "to" string.
     */
    private function replaceReferences($source)
    {
        return preg_replace_callback('/\\\([\d]+)/', [$this, 'replaceReferencesCallback'], $this->to);
    }

    /**
     * Gets the matched group from the Regexp engine.
     *
     * @param array $matches Matched elements.
     *
     * @return string
     */
    private function replaceReferencesCallback($matches)
    {
        return (string) $this->reg->getGroup($matches[1]);
    }
}
